import sys
import os
os.environ['OPENCV_IO_ENABLE_OPENEXR'] = '1'
import re
from PIL import Image, ImageEnhance, ImageDraw, ImageFont, ImageOps, ImageFilter

def parse_color(color_str):
    """Parse color string (like #ffffff, rgb(255,255,255), rgba(255,255,255,0.6), white) to RGBA/RGB tuple."""
    color_str = color_str.strip('"\' ')
    
    # 1. Parse rgba(r,g,b,a)
    rgba_match = re.match(r'^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d\.]+)\s*)?\)$', color_str, re.IGNORECASE)
    if rgba_match:
        r = int(rgba_match.group(1))
        g = int(rgba_match.group(2))
        b = int(rgba_match.group(3))
        a = rgba_match.group(4)
        if a is not None:
            # If alpha is specified as a float, convert to 0-255 integer
            alpha = float(a)
            if alpha <= 1.0:
                alpha = int(alpha * 255)
            else:
                alpha = int(alpha)
            return (r, g, b, alpha)
        return (r, g, b, 255)

    # 2. Parse hex
    if color_str.startswith('#'):
        hex_val = color_str.lstrip('#')
        if len(hex_val) == 3:
            hex_val = ''.join([c*2 for c in hex_val])
        if len(hex_val) == 8: # RRGGBBAA
            return tuple(int(hex_val[i:i+2], 16) for i in (0, 2, 4, 6))
        return tuple(int(hex_val[i:i+2], 16) for i in (0, 2, 4))
    
    # Simple color name mapping
    names = {
        'white': (255, 255, 255, 255),
        'black': (0, 0, 0, 255),
        'red': (255, 0, 0, 255),
        'green': (0, 255, 0, 255),
        'blue': (0, 0, 255, 255),
        'yellow': (255, 255, 0, 255),
        'magenta': (255, 0, 255, 255),
        'cyan': (0, 255, 255, 255),
        'gray': (128, 128, 128, 255)
    }
    return names.get(color_str.lower(), (255, 255, 255, 255))

def is_option(arg):
    if not (arg.startswith('-') or arg.startswith('+')):
        return False
    if len(arg) < 2:
        return False
    if arg[1].isdigit():
        return False
    return True

def load_image_with_fallbacks(input_path):
    ext = os.path.splitext(input_path)[1].lower()
    
    # Register HEIF/AVIF openers if available
    try:
        from pillow_heif import register_heif_opener
        register_heif_opener()
    except ImportError:
        pass
        
    try:
        import pillow_avif
    except ImportError:
        pass

    # PSD/PSB
    if ext in ('.psd',):
        try:
            from psd_tools import PSDImage
            psd = PSDImage.open(input_path)
            return psd.composite()
        except Exception as e:
            print(f"PSD fallback warning: {e}")

    # RAW formats
    if ext in ('.raw', '.cr2', '.cr3', '.nef', '.arw', '.dng', '.orf', '.raf'):
        try:
            import rawpy
            with rawpy.imread(input_path) as raw:
                rgb = raw.postprocess(use_camera_wb=True)
                return Image.fromarray(rgb)
        except Exception as e:
            print(f"RAW fallback warning: {e}")

    # SVG
    if ext in ('.svg',):
        try:
            from svglib.svglib import svg2rlg
            from reportlab.graphics import renderPM
            import io
            drawing = svg2rlg(input_path)
            png_data = io.BytesIO()
            renderPM.drawToFile(drawing, png_data, fmt='PNG')
            png_data.seek(0)
            return Image.open(png_data)
        except Exception as e:
            print(f"SVG fallback warning: {e}")

    # PDF / AI / EPS
    if ext in ('.pdf', '.ai', '.eps'):
        try:
            import fitz  # PyMuPDF
            doc = fitz.open(input_path)
            if len(doc) > 0:
                page = doc[0]
                pix = page.get_pixmap(dpi=150)
                import io
                img_data = pix.tobytes("png")
                return Image.open(io.BytesIO(img_data))
        except Exception as e:
            print(f"PDF/AI/EPS fallback warning: {e}")

    # HDR / EXR
    if ext in ('.hdr', '.exr'):
        try:
            import cv2
            import numpy as np
            cv_img = cv2.imread(input_path, cv2.IMREAD_UNCHANGED)
            if cv_img is not None:
                if len(cv_img.shape) == 3:
                    if cv_img.shape[2] == 4:
                        cv_img = cv2.cvtColor(cv_img, cv2.COLOR_BGRA2RGBA)
                    else:
                        cv_img = cv2.cvtColor(cv_img, cv2.COLOR_BGR2RGB)
                if cv_img.dtype == np.float32:
                    cv_img = np.clip(cv_img * 255.0, 0, 255).astype(np.uint8)
                return Image.fromarray(cv_img)
        except Exception as e:
            print(f"HDR/EXR fallback warning: {e}")

    # Default Pillow open
    return Image.open(input_path)

def save_image_with_fallbacks(img, output_path, fmt, quality):
    fmt = fmt.upper()
    if fmt == 'JPG' or fmt == 'JFIF':
        fmt = 'JPEG'
    if fmt == 'TIF':
        fmt = 'TIFF'
    if fmt == 'HEIC' or fmt == 'HEIF':
        fmt = 'HEIF'
    if fmt == 'PSD':
        # Save as PNG since Pillow doesn't write PSD natively
        fmt = 'PNG'
    if fmt in ('RAW', 'CR2', 'CR3', 'NEF', 'ARW', 'DNG', 'ORF', 'RAF'):
        # Save camera RAW targets as lossless TIFF containers
        fmt = 'TIFF'
        
    # Register HEIF/AVIF openers if available
    try:
        from pillow_heif import register_heif_opener
        register_heif_opener()
    except ImportError:
        pass
        
    try:
        import pillow_avif
    except ImportError:
        pass

    if fmt == 'SVG':
        import base64
        import io
        in_mem = io.BytesIO()
        img.save(in_mem, format='PNG')
        b64_data = base64.b64encode(in_mem.getvalue()).decode('utf-8')
        svg_content = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {img.width} {img.height}" width="{img.width}" height="{img.height}">
  <image width="{img.width}" height="{img.height}" href="data:image/png;base64,{b64_data}"/>
</svg>'''
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write(svg_content)
        return

    if fmt in ('EXR', 'HDR'):
        import cv2
        import numpy as np
        np_img = np.array(img)
        if fmt == 'EXR':
            # EXR expects float32
            np_img = np_img.astype(np.float32) / 255.0
        if len(np_img.shape) == 3:
            if np_img.shape[2] == 4:
                np_img = cv2.cvtColor(np_img, cv2.COLOR_RGBA2BGRA)
            else:
                np_img = cv2.cvtColor(np_img, cv2.COLOR_RGB2BGR)
        cv2.imwrite(output_path, np_img)
        return

    if img.mode in ('RGBA', 'LA') and fmt == 'JPEG':
        bg = Image.new("RGBA", img.size, (255, 255, 255, 255))
        img = Image.alpha_composite(bg, img).convert("RGB")
    elif img.mode != 'RGB' and fmt == 'JPEG':
        img = img.convert('RGB')
        
    img.save(output_path, format=fmt, quality=quality)

def main():
    if len(sys.argv) < 3:
        print("Usage: magick_fallback.py <input> [options] <output>")
        sys.exit(1)
        
    # Separate options and positional arguments
    # Positional args are files. The first (or first two for composite) are inputs, the last is output.
    args = sys.argv[1:]
    options = []
    positionals = []
    overlay_path = None
    overlay_options = []
    
    in_parens = False
    
    i = 0
    while i < len(args):
        arg = args[i]
        if arg == '(':
            in_parens = True
            i += 1
            continue
        elif arg == ')':
            in_parens = False
            i += 1
            continue
            
        if in_parens:
            if is_option(arg):
                opt = arg
                params = []
                i += 1
                while i < len(args) and not is_option(args[i]):
                    if args[i] == ')' or args[i] == '(':
                        break
                    params.append(args[i])
                    i += 1
                overlay_options.append((opt, params))
            else:
                overlay_path = arg
                i += 1
        else:
            if is_option(arg):
                opt = arg
                params = []
                i += 1
                while i < len(args) and not is_option(args[i]):
                    if args[i] == ')' or args[i] == '(':
                        break
                    # The very last argument is always the output file, don't eat it as a parameter!
                    if i == len(args) - 1:
                        break
                    params.append(args[i])
                    i += 1
                options.append((opt, params))
            else:
                positionals.append(arg)
                i += 1
            
    if len(positionals) < 2:
        print("Error: Missing input or output path.")
        sys.exit(1)
        
    input_path = positionals[0]
    output_path = positionals[-1]
    
    if not os.path.exists(input_path):
        print(f"Error: Input file does not exist: {input_path}")
        sys.exit(1)
        
    try:
        img = load_image_with_fallbacks(input_path)
        # Convert palette/binary/CMYK images to RGB/RGBA to avoid "ValueError: image has wrong mode" on filters & enhancements
        if img.mode == 'P':
            if 'transparency' in img.info or img.info.get('transparency') is not None:
                img = img.convert('RGBA')
            else:
                img = img.convert('RGB')
        elif img.mode == '1':
            img = img.convert('L')
        elif img.mode == 'CMYK':
            img = img.convert('RGB')
    except Exception as e:
        print(f"Error: Failed to open image: {e}")
        sys.exit(1)
        
    # Global state settings
    quality = 85
    bg_color = (255, 255, 255, 0) # default transparent/white
    gravity = 'Center'
    font_name = 'Arial'
    font_size = 30
    fill_color = 'white'
    geometry_offset = (0, 0)
    current_channel = 'All'
    
    # Apply options sequentially
    for opt, params in options:
        opt_name = opt.lower()
        
        if opt_name == '-resize':
            geom = params[0]
            w, h = img.size
            maintain_ratio = True
            if '!' in geom:
                maintain_ratio = False
                geom = geom.replace('!', '')
                
            if 'x' in geom:
                parts = geom.split('x')
                target_w = int(parts[0]) if parts[0] else 0
                target_h = int(parts[1]) if parts[1] else 0
            else:
                target_w = int(geom)
                target_h = 0
                
            if maintain_ratio:
                if target_w > 0 and target_h > 0:
                    ratio = min(target_w / w, target_h / h)
                    img = img.resize((int(w * ratio), int(h * ratio)), Image.Resampling.LANCZOS)
                elif target_w > 0:
                    ratio = target_w / w
                    img = img.resize((target_w, int(h * ratio)), Image.Resampling.LANCZOS)
                elif target_h > 0:
                    ratio = target_h / h
                    img = img.resize((int(w * ratio), target_h), Image.Resampling.LANCZOS)
            else:
                target_w = target_w or w
                target_h = target_h or h
                img = img.resize((target_w, target_h), Image.Resampling.LANCZOS)
                
        elif opt_name == '-quality':
            quality = int(params[0])
            
        elif opt_name == '-background':
            bg_color = parse_color(params[0])
            
        elif opt_name == '-gravity':
            gravity = params[0].lower()
            
        elif opt_name == '-geometry':
            # Geometry offset parser (e.g. +20+20)
            geom = params[0]
            match = re.match(r'([+-]\d+)([+-]\d+)', geom)
            if match:
                geometry_offset = (int(match.group(1)), int(match.group(2)))
                
        elif opt_name == '-font':
            font_name = params[0]
            
        elif opt_name == '-pointsize':
            font_size = int(params[0])
            
        elif opt_name == '-fill':
            fill_color = parse_color(params[0])
            
        elif opt_name == '-channel':
            current_channel = params[0].upper()
            
        elif opt_name == '+channel':
            current_channel = 'All'
            
        elif opt_name == '-evaluate' and len(params) >= 2 and params[0].lower() == 'multiply':
            factor = float(params[1])
            if current_channel == 'All':
                enhancer = ImageEnhance.Brightness(img)
                img = enhancer.enhance(factor)
            else:
                # Apply evaluation to specific band
                if img.mode != 'RGB' and img.mode != 'RGBA':
                    img = img.convert('RGB')
                bands = list(img.split())
                mode_bands = list(img.mode)
                
                # Check channel band index
                chan_idx = -1
                if current_channel == 'R' and 'R' in mode_bands:
                    chan_idx = mode_bands.index('R')
                elif current_channel == 'G' and 'G' in mode_bands:
                    chan_idx = mode_bands.index('G')
                elif current_channel == 'B' and 'B' in mode_bands:
                    chan_idx = mode_bands.index('B')
                elif current_channel == 'A':
                    if img.mode != 'RGBA':
                        img = img.convert('RGBA')
                    bands = list(img.split())
                    mode_bands = list(img.mode)
                    if 'A' in mode_bands:
                        chan_idx = mode_bands.index('A')
                    
                if chan_idx != -1:
                    band = bands[chan_idx]
                    bands[chan_idx] = band.point(lambda p: min(255, max(0, int(p * factor))))
                    img = Image.merge(img.mode, bands)
                    
        elif opt_name == '-modulate':
            parts = params[0].split(',')
            b_factor = float(parts[0]) / 100.0 if len(parts) > 0 else 1.0
            s_factor = float(parts[1]) / 100.0 if len(parts) > 1 else 1.0
            if b_factor != 1.0:
                img = ImageEnhance.Brightness(img).enhance(b_factor)
            if s_factor != 1.0:
                img = ImageEnhance.Color(img).enhance(s_factor)
                
        elif opt_name == '-sharpen':
            val = params[0]
            # parse radius
            if 'x' in val:
                radius = float(val.split('x')[1])
            else:
                radius = 1.0
            img = img.filter(ImageFilter.UnsharpMask(radius=radius, percent=150, threshold=3))
            
        elif opt_name == '-blur':
            val = params[0]
            if 'x' in val:
                radius = float(val.split('x')[1])
            else:
                radius = 1.0
            img = img.filter(ImageFilter.GaussianBlur(radius=radius))
            
        elif opt_name in ('-sigmoidal-contrast', '+sigmoidal-contrast'):
            val = params[0]
            factor = float(val.split('x')[0])
            # sigmoidal contrast approximation
            if opt_name.startswith('+'):
                img = ImageEnhance.Contrast(img).enhance(max(0.0, 1.0 - (factor / 10.0)))
            else:
                img = ImageEnhance.Contrast(img).enhance(1.0 + (factor / 10.0))
            
        elif opt_name == '-level':
            val = params[0]
            if ',' in val:
                parts = val.split(',')
                if len(parts) == 3:
                    try:
                        gamma = float(parts[2])
                        if gamma != 1.0:
                            # Apply simple gamma correction
                            if img.mode != 'RGB' and img.mode != 'RGBA':
                                img = img.convert('RGB')
                            bands = list(img.split())
                            for idx in range(min(3, len(bands))):
                                bands[idx] = bands[idx].point(lambda p: min(255, max(0, int(255 * ((p / 255.0) ** (1.0 / gamma))))))
                            img = Image.merge(img.mode, bands)
                    except Exception as e:
                        print("Level gamma error:", e)
                        
        elif opt_name == '-colorspace' and params[0].lower() == 'gray':
            img = img.convert('L')
            
        elif opt_name == '-contrast-stretch':
            img = ImageOps.autocontrast(img)
            
        elif opt_name == '-sepia-tone':
            # Apply sepia filter matrix
            matrix = [
                0.393, 0.769, 0.189, 0,
                0.349, 0.686, 0.168, 0,
                0.272, 0.534, 0.131, 0
            ]
            if img.mode in ('RGBA', 'LA'):
                alpha = img.split()[-1]
                img = img.convert('RGB').convert('RGB', matrix=matrix).convert('RGBA')
                img.putalpha(alpha)
            else:
                img = img.convert('RGB').convert('RGB', matrix=matrix)
                
        elif opt_name == '-alpha' and params[0].lower() == 'remove':
            # Flatten image on background
            if img.mode in ('RGBA', 'LA'):
                bg = Image.new("RGBA", img.size, bg_color if len(bg_color) == 4 else bg_color + (255,))
                img = Image.alpha_composite(bg, img.convert("RGBA")).convert("RGB")
                
        elif opt_name == '-alpha' and params[0].lower() == 'off':
            # Remove alpha channel if RGB
            if img.mode == 'RGBA':
                img = img.convert('RGB')
                
        elif opt_name == '-extent':
            # Canvas extent
            geom = params[0]
            parts = geom.split('x')
            ext_w = int(parts[0])
            ext_h = int(parts[1])
            
            # Create new background image
            bg = Image.new(img.mode, (ext_w, ext_h), bg_color if len(bg_color) == 4 else bg_color + (255,) if img.mode == 'RGBA' else bg_color)
            
            # Calculate position based on gravity
            w, h = img.size
            x, y = 0, 0
            
            if 'north' in gravity:
                y = 0
            elif 'south' in gravity:
                y = ext_h - h
            else: # Center/Middle
                y = (ext_h - h) // 2
                
            if 'west' in gravity:
                x = 0
            elif 'east' in gravity:
                x = ext_w - w
            else: # Center/Middle
                x = (ext_w - w) // 2
                
            # Add geometry offset
            x += geometry_offset[0]
            y += geometry_offset[1]
            
            # Paste image
            bg.paste(img, (x, y), img if img.mode == 'RGBA' else None)
            img = bg
            
        elif opt_name == '-annotate':
            # Text watermark: -annotate geometry text
            geom = params[0]
            text = params[1]
            
            # Parse geometry (e.g. 0x0+20+20 or 45x45+10+10 or 0)
            rotation = 0
            off_x = 0
            off_y = 0
            match = re.match(r'(-?\d+)x(-?\d+)([+-]\d+)([+-]\d+)', geom)
            if match:
                rotation = int(match.group(1))
                off_x = int(match.group(3))
                off_y = int(match.group(4))
            else:
                try:
                    rotation = int(geom)
                except:
                    pass
            
            w, h = img.size
            
            # Use default font or fallback
            try:
                font = ImageFont.load_default()
            except:
                font = None
                
            # Create a temporary canvas just for the text bounding box measurement
            measure_img = Image.new('RGBA', (1, 1), (0, 0, 0, 0))
            measure_draw = ImageDraw.Draw(measure_img)
            bbox = measure_draw.textbbox((0, 0), text, font=font)
            text_w = bbox[2] - bbox[0]
            text_h = bbox[3] - bbox[1]
            
            # Create text layer image
            pad = 20
            txt_img = Image.new('RGBA', (text_w + pad, text_h + pad), (0, 0, 0, 0))
            txt_draw = ImageDraw.Draw(txt_img)
            txt_draw.text((pad // 2, pad // 2), text, fill=fill_color, font=font)
            
            # Rotate the text layer
            if rotation != 0:
                txt_img = txt_img.rotate(rotation, resample=Image.Resampling.BICUBIC, expand=True)
                text_w, text_h = txt_img.size
            else:
                text_w, text_h = txt_img.size
                
            # Calculate coordinates based on gravity
            x, y = 0, 0
            gravity_lower = gravity.lower()
            if 'north' in gravity_lower:
                y = off_y
            elif 'south' in gravity_lower:
                y = h - text_h - off_y
            else: # Center/Middle
                y = (h - text_h) // 2 + off_y
                
            if 'west' in gravity_lower:
                x = off_x
            elif 'east' in gravity_lower:
                x = w - text_w - off_x
            else: # Center/Middle
                x = (w - text_w) // 2 + off_x
                
            # Blend onto main image
            if img.mode != 'RGBA':
                img = img.convert('RGBA')
            img.paste(txt_img, (x, y), txt_img)
            
        elif opt_name == '-composite':
            # Overlay image on top
            ol_path = overlay_path if overlay_path else (positionals[1] if len(positionals) >= 2 else None)
            if ol_path and os.path.exists(ol_path):
                try:
                    overlay = load_image_with_fallbacks(ol_path)
                    # Convert palette/binary/CMYK images to RGB/RGBA
                    if overlay.mode == 'P':
                        if 'transparency' in overlay.info or overlay.info.get('transparency') is not None:
                            overlay = overlay.convert('RGBA')
                        else:
                            overlay = overlay.convert('RGB')
                    elif overlay.mode == '1':
                        overlay = overlay.convert('L')
                    elif overlay.mode == 'CMYK':
                        overlay = overlay.convert('RGB')
                    
                    # Apply overlay_options to the overlay image first
                    overlay_channel = 'All'
                    for ol_opt, ol_params in overlay_options:
                        ol_opt_name = ol_opt.lower()
                        if ol_opt_name == '-resize':
                            geom = ol_params[0]
                            w_ol, h_ol = overlay.size
                            maintain_ratio = True
                            if '!' in geom:
                                maintain_ratio = False
                                geom = geom.replace('!', '')
                                
                            if 'x' in geom:
                                parts = geom.split('x')
                                target_w = int(parts[0]) if parts[0] else 0
                                target_h = int(parts[1]) if parts[1] else 0
                            else:
                                target_w = int(geom)
                                target_h = 0
                                
                            if maintain_ratio:
                                if target_w > 0 and target_h > 0:
                                    ratio = min(target_w / w_ol, target_h / h_ol)
                                    overlay = overlay.resize((int(w_ol * ratio), int(h_ol * ratio)), Image.Resampling.LANCZOS)
                                elif target_w > 0:
                                    ratio = target_w / w_ol
                                    overlay = overlay.resize((target_w, int(h_ol * ratio)), Image.Resampling.LANCZOS)
                                elif target_h > 0:
                                    ratio = target_h / h_ol
                                    overlay = overlay.resize((int(w_ol * ratio), target_h), Image.Resampling.LANCZOS)
                            else:
                                target_w = target_w or w_ol
                                target_h = target_h or h_ol
                                overlay = overlay.resize((target_w, target_h), Image.Resampling.LANCZOS)
                                
                        elif ol_opt_name == '-channel':
                            overlay_channel = ol_params[0].upper()
                        elif ol_opt_name == '+channel':
                            overlay_channel = 'All'
                        elif ol_opt_name == '-evaluate' and len(ol_params) >= 2 and ol_params[0].lower() == 'multiply':
                            factor = float(ol_params[1])
                            if overlay_channel == 'All':
                                enhancer = ImageEnhance.Brightness(overlay)
                                overlay = enhancer.enhance(factor)
                            else:
                                if overlay_channel == 'A':
                                    if overlay.mode != 'RGBA':
                                        overlay = overlay.convert('RGBA')
                                bands = list(overlay.split())
                                mode_bands = list(overlay.mode)
                                
                                chan_idx = -1
                                if overlay_channel == 'R' and 'R' in mode_bands:
                                    chan_idx = mode_bands.index('R')
                                elif overlay_channel == 'G' and 'G' in mode_bands:
                                    chan_idx = mode_bands.index('G')
                                elif overlay_channel == 'B' and 'B' in mode_bands:
                                    chan_idx = mode_bands.index('B')
                                elif overlay_channel == 'A' and 'A' in mode_bands:
                                    chan_idx = mode_bands.index('A')
                                    
                                if chan_idx != -1:
                                    band = bands[chan_idx]
                                    bands[chan_idx] = band.point(lambda p: min(255, max(0, int(p * factor))))
                                    overlay = Image.merge(overlay.mode, bands)
                                    
                    w, h = img.size
                    ol_w, ol_h = overlay.size
                    
                    # Calculate gravity offset
                    x, y = 0, 0
                    if 'north' in gravity:
                        y = 0
                    elif 'south' in gravity:
                        y = h - ol_h
                    else:
                        y = (h - ol_h) // 2
                        
                    if 'west' in gravity:
                        x = 0
                    elif 'east' in gravity:
                        x = w - ol_w
                    else:
                        x = (w - ol_w) // 2
                        
                    x += geometry_offset[0]
                    y += geometry_offset[1]
                    
                    img.paste(overlay, (x, y), overlay if overlay.mode == 'RGBA' else None)
                except Exception as e:
                    print("Composite error:", e)
                        
        elif opt_name == '-fuzz':
            fuzz_pct = int(params[0].replace('%', ''))
            fuzz_val = (fuzz_pct / 100.0) * 255.0
            
            # Find next -fill and -opaque options
            fill_val = None
            opaque_val = None
            
            for next_opt, next_params in options:
                if next_opt.lower() == '-fill':
                    fill_val = parse_color(next_params[0])
                elif next_opt.lower() == '-opaque':
                    opaque_val = parse_color(next_params[0])
                    
            if fill_val and opaque_val:
                # Apply color replacement with fuzz
                import numpy as np
                img_rgba = img.convert('RGBA')
                data = np.array(img_rgba)
                r, g, b, a = data[:,:,0], data[:,:,1], data[:,:,2], data[:,:,3]
                
                # Check distance from opaque color
                dist = np.sqrt((r - opaque_val[0])**2 + (g - opaque_val[1])**2 + (b - opaque_val[2])**2)
                mask = dist <= fuzz_val
                
                # Replace matching colors
                data[mask, 0] = fill_val[0]
                data[mask, 1] = fill_val[1]
                data[mask, 2] = fill_val[2]
                if len(fill_val) == 4:
                    data[mask, 3] = fill_val[3]
                    
                img = Image.fromarray(data, 'RGBA')
                
    # Save target output
    try:
        fmt = output_path.split('.')[-1].upper()
        save_image_with_fallbacks(img, output_path, fmt, quality)
        print("Success: Image written to", output_path)
        sys.exit(0)
    except Exception as e:
        print(f"Error: Failed to write image: {e}")
        sys.exit(1)

if __name__ == '__main__':
    main()
