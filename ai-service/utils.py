import os
import numpy as np
from PIL import Image
import cv2

def load_cv2_image(input_path: str, flags: int = cv2.IMREAD_COLOR) -> np.ndarray or None:
    """
    Load any image format using OpenCV or Pillow/magick fallbacks, 
    and return a numpy array compatible with OpenCV.
    """
    if not os.path.exists(input_path):
        return None

    ext = os.path.splitext(input_path)[1].lower()

    # Try standard cv2.imread first for standard formats
    if ext in ('.jpg', '.jpeg', '.png', '.bmp', '.webp', '.tiff', '.tif'):
        try:
            img = cv2.imread(input_path, flags)
            if img is not None:
                return img
        except Exception:
            pass

    # Fallbacks for HEIF/AVIF openers
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
    if ext in ('.psd', '.psb'):
        try:
            from psd_tools import PSDImage
            psd = PSDImage.open(input_path)
            img_pil = psd.composite()
            if img_pil is not None:
                return cv2.cvtColor(np.array(img_pil), cv2.COLOR_RGBA2BGRA if img_pil.mode == 'RGBA' else cv2.COLOR_RGB2BGR)
        except Exception as e:
            print(f"PSD load warning: {e}")

    # RAW formats
    if ext in ('.raw', '.cr2', '.cr3', '.nef', '.arw', '.dng', '.orf', '.raf'):
        try:
            import rawpy
            with rawpy.imread(input_path) as raw:
                rgb = raw.postprocess(use_camera_wb=True)
                return cv2.cvtColor(rgb, cv2.COLOR_RGB2BGR)
        except Exception as e:
            print(f"RAW load warning: {e}")

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
            img_pil = Image.open(png_data)
            return cv2.cvtColor(np.array(img_pil), cv2.COLOR_RGBA2BGRA if img_pil.mode == 'RGBA' else cv2.COLOR_RGB2BGR)
        except Exception as e:
            print(f"SVG load warning: {e}")

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
                img_pil = Image.open(io.BytesIO(img_data))
                return cv2.cvtColor(np.array(img_pil), cv2.COLOR_RGBA2BGRA if img_pil.mode == 'RGBA' else cv2.COLOR_RGB2BGR)
        except Exception as e:
            print(f"PDF/AI/EPS load warning: {e}")

    # HDR / EXR
    if ext in ('.hdr', '.exr'):
        try:
            cv_img = cv2.imread(input_path, cv2.IMREAD_UNCHANGED)
            if cv_img is not None:
                if len(cv_img.shape) == 3:
                    if cv_img.shape[2] == 4:
                        cv_img = cv2.cvtColor(cv_img, cv2.COLOR_BGRA2RGBA)
                    else:
                        cv_img = cv2.cvtColor(cv_img, cv2.COLOR_BGR2RGB)
                if cv_img.dtype == np.float32:
                    cv_img = np.clip(cv_img * 255.0, 0, 255).astype(np.uint8)
                return cv2.cvtColor(cv_img, cv2.COLOR_RGB2BGR)
        except Exception as e:
            print(f"HDR/EXR load warning: {e}")

    # Default Pillow fallback
    try:
        img_pil = Image.open(input_path)
        # Convert grayscale/palette/RGBA to standard BGR/BGRA
        if img_pil.mode == 'RGBA':
            if flags == cv2.IMREAD_UNCHANGED:
                return cv2.cvtColor(np.array(img_pil), cv2.COLOR_RGBA2BGRA)
            return cv2.cvtColor(np.array(img_pil), cv2.COLOR_RGBA2BGR)
        elif img_pil.mode in ('P', '1', 'L'):
            img_pil = img_pil.convert('RGB')
        return cv2.cvtColor(np.array(img_pil), cv2.COLOR_RGB2BGR)
    except Exception as e:
        print(f"Pillow load fallback failed: {e}")
        return None
