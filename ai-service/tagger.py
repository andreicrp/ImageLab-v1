import os
import cv2
import numpy as np

from utils import load_cv2_image

def generate_image_tags(image_path: str) -> list:
    """
    Generate automatic tags based on image features, color histogram, aspect ratios, and details.
    """
    tags = []
    if not os.path.exists(image_path):
        return tags

    try:
        img = load_cv2_image(image_path)
        if img is None:
            return tags

        h, w = img.shape[:2]
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        hsv = cv2.cvtColor(img, cv2.COLOR_BGR2HSV)

        # 1. Face Detection -> portrait
        cascade_path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
        face_cascade = cv2.CascadeClassifier(cascade_path)
        faces = face_cascade.detectMultiScale(gray, 1.1, 4, minSize=(30, 30))
        has_face = len(faces) > 0
        if has_face:
            tags.append("portrait")
            
        # 2. Brightness Analysis -> low light
        mean_brightness = np.mean(gray)
        if mean_brightness < 75:
            tags.append("low light")
        elif mean_brightness > 200:
            tags.append("high exposure")

        # 3. Sharpness -> high detail
        lap_var = cv2.Laplacian(gray, cv2.CV_64F).var()
        if lap_var > 250:
            tags.append("high detail")
        elif lap_var < 50:
            tags.append("soft focus")

        # 4. Color / HSV Analysis -> outdoor, monochrome, colorful
        # Check Saturation channel
        s_channel = hsv[:, :, 1]
        mean_sat = np.mean(s_channel)
        if mean_sat < 12:
            tags.append("monochrome")
        elif mean_sat > 150:
            tags.append("vibrant")

        # Sky and nature green detection for "outdoor"
        # Sky range: Hue 100-140, Saturation 40+, Value 50+
        sky_mask = cv2.inRange(hsv, (100, 40, 50), (140, 255, 255))
        # Foliage range: Hue 30-85, Saturation 30+, Value 30+
        green_mask = cv2.inRange(hsv, (30, 30, 30), (85, 255, 255))
        
        # Sky is usually at the top
        top_sky_ratio = np.mean(sky_mask[:int(h * 0.45), :]) / 255.0
        green_ratio = np.mean(green_mask) / 255.0
        
        if top_sky_ratio > 0.08 or green_ratio > 0.12:
            tags.append("outdoor")
            if w / h >= 1.4:
                tags.append("landscape")
        else:
            if not has_face and mean_brightness >= 75:
                # Border check for "product"
                # A product shot typically has solid background around borders
                border_width = int(min(w, h) * 0.05)
                borders = [
                    gray[0:border_width, :], # Top
                    gray[h-border_width:h, :], # Bottom
                    gray[:, 0:border_width], # Left
                    gray[:, w-border_width:w] # Right
                ]
                border_std = np.mean([np.std(b) for b in borders])
                if border_std < 15 and (0.75 <= w/h <= 1.35):
                    tags.append("product")

        # Fallback general categorization
        if not tags:
            tags.append("general photo")

        return tags

    except Exception as e:
        print(f"Tag generation error: {str(e)}")
        return ["error"]
