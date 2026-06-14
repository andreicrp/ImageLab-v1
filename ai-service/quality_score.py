import os
import cv2
import numpy as np
from utils import load_cv2_image

def analyze_image_quality(image_path: str) -> dict:
    """
    Analyze image quality (sharpness, noise, exposure, resolution) and output a score 0-100
    along with recommended presets/enhancements.
    """
    if not os.path.exists(image_path):
        return {"success": False, "error": "File not found"}
        
    try:
        img = load_cv2_image(image_path)
        if img is None:
            return {"success": False, "error": "Invalid image file"}
            
        h, w = img.shape[:2]
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        
        # 1. Sharpness (Laplacian variance)
        # Higher is sharper. Typically, < 100 is blurry, > 500 is very sharp.
        lap_var = cv2.Laplacian(gray, cv2.CV_64F).var()
        # Normalize to 0-100 score
        sharpness_score = int(min(100, max(0, (np.log1p(lap_var) / 7.0) * 100)))
        
        # 2. Noise Level
        # Estimate noise by calculating absolute difference from a median-filtered version
        median = cv2.medianBlur(gray, 3)
        noise_diff = cv2.absdiff(gray, median)
        mean_noise = np.mean(noise_diff)
        # Normalize: average noise of 0 is 100 score, noise of 15 or more is 0 score
        noise_score = int(max(0, min(100, 100 - (mean_noise * 6.6))))
        
        # 3. Exposure
        # Ideal mean brightness is around 120-130.
        mean_brightness = np.mean(gray)
        # Calculate exposure score based on distance from 127
        exposure_diff = abs(mean_brightness - 127)
        exposure_score = int(max(0, min(100, 100 - (exposure_diff * 0.78))))
        
        # 4. Resolution
        # Standardize score based on Full HD area (1920 * 1080)
        pixel_count = w * h
        target_pixels = 1920 * 1080
        resolution_score = int(min(100, max(10, (pixel_count / target_pixels) * 100)))
        
        # 5. Color Cast / White Balance analysis
        # Calculate mean of blue, green, and red channels
        mean_b, mean_g, mean_r = cv2.mean(img)[:3]
        # temp_cast: positive is warm/red, negative is cool/blue
        temp_cast = int(mean_r - mean_b)
        
        # Weighted overall score
        overall_score = int(
            (sharpness_score * 0.3) +
            (noise_score * 0.2) +
            (exposure_score * 0.25) +
            (resolution_score * 0.25)
        )
        
        # Check if face exists for preset suggestions
        cascade_path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
        face_cascade = cv2.CascadeClassifier(cascade_path)
        faces = face_cascade.detectMultiScale(gray, 1.1, 4, minSize=(30, 30))
        has_face = len(faces) > 0
        
        # Determine preset suggestions
        suggestions = []
        
        if resolution_score < 70:
            suggestions.append({
                "action": "Upscale Image x2",
                "reason": "Image resolution is low. Upscaling will enhance sharpness and detail."
            })
            
        if has_face and sharpness_score < 80:
            suggestions.append({
                "action": "Face Enhancement",
                "reason": "Face detected with low clarity. Face enhancement will restore sharp details."
            })
        elif sharpness_score < 60:
            suggestions.append({
                "action": "Increase Sharpness",
                "reason": "The image appears slightly blurry. Applying sharpness will highlight textures."
            })
            
        if exposure_score < 70:
            suggestions.append({
                "action": "Auto Enhance / Exposure Correction",
                "reason": f"Under/Overexposed (Mean Brightness: {mean_brightness:.1f}). Correcting exposure will recover highlights/shadows."
            })
            
        if abs(temp_cast) > 18:
            suggestions.append({
                "action": "White Balance Correction",
                "reason": f"Significant color cast detected ({'Warm/Reddish' if temp_cast > 0 else 'Cool/Bluish'} cast). Correcting white balance will restore natural tones."
            })
            
        if noise_score < 70:
            suggestions.append({
                "action": "Reduce Noise",
                "reason": "High luminance noise detected. Denoising will smooth flat surfaces."
            })

        # Product image heuristic: solid background/high contrast
        # If image has an aspect ratio close to square, and standard deviation of borders is small, recommend BG removal
        is_square = 0.8 <= (w / h) <= 1.25
        if is_square and not has_face:
            suggestions.append({
                "action": "Background Removal",
                "reason": "Recommended for product/isolated subject categorization."
            })

        return {
            "success": True,
            "overall_score": overall_score,
            "metrics": {
                "sharpness": sharpness_score,
                "noise": noise_score,
                "exposure": exposure_score,
                "resolution": resolution_score,
                "brightness_val": int(mean_brightness),
                "temp_cast": temp_cast
            },
            "suggestions": suggestions,
            "has_face": has_face
        }
        
    except Exception as e:
        return {"success": False, "error": str(e)}

