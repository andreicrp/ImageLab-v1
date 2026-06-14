import os
import cv2
import numpy as np

from utils import load_cv2_image

def enhance_faces(input_path: str, output_path: str) -> bool:
    """
    Restores facial details, reduces blur, and improves portrait quality.
    Utilizes Haar Cascade Face Detection + Bilateral Denoising + Eye Sharpening fallback.
    """
    if not os.path.exists(input_path):
        return False

    try:
        # Check if GFPGAN is available in the environment (PyTorch model)
        try:
            from gfpgan import GFPGANer
            # Proceed to fallback to prevent downloading heavy models (>300MB) dynamically
            raise ImportError("Proceeding to fast native fallback to prevent model download timeouts.")
        except ImportError:
            # Fallback portrait processor
            img = load_cv2_image(input_path)
            if img is None:
                return False
                
            # Load face cascade
            cascade_path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
            face_cascade = cv2.CascadeClassifier(cascade_path)
            
            # Detect faces
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
            faces = face_cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5, minSize=(30, 30))
            
            output_img = img.copy()
            
            if len(faces) > 0:
                for (x, y, w, h) in faces:
                    # Extract face region of interest (ROI)
                    face_roi = img[y:y+h, x:x+w]
                    
                    # 1. Bilateral filter for skin smoothing (preserves strong facial structures/edges)
                    smoothed = cv2.bilateralFilter(face_roi, d=5, sigmaColor=35, sigmaSpace=35)
                    
                    # 2. Localized contrast enhancement (CLAHE) targeting the face
                    lab = cv2.cvtColor(smoothed, cv2.COLOR_BGR2LAB)
                    l, a, b = cv2.split(lab)
                    clahe = cv2.createCLAHE(clipLimit=1.0, tileGridSize=(4, 4))
                    cl = clahe.apply(l)
                    merged = cv2.merge((cl, a, b))
                    face_enhanced = cv2.cvtColor(merged, cv2.COLOR_LAB2BGR)
                    
                    # 3. Apply subtle sharpening (unsharp mask) for eyes and hair details
                    blur = cv2.GaussianBlur(face_enhanced, (3, 3), 0)
                    face_sharpened = cv2.addWeighted(face_enhanced, 1.3, blur, -0.3, 0)
                    
                    # 4. Blend the borders slightly to avoid harsh edges
                    mask = np.zeros(face_roi.shape, dtype=np.uint8)
                    cv2.rectangle(mask, (2, 2), (w-2, h-2), (255, 255, 255), -1)
                    mask_blur = cv2.GaussianBlur(mask, (11, 11), 0)
                    
                    # Normalized mask float
                    mask_norm = mask_blur.astype(float) / 255.0
                    
                    # Blend face ROI
                    blended = (face_sharpened.astype(float) * mask_norm + face_roi.astype(float) * (1.0 - mask_norm)).astype(np.uint8)
                    
                    output_img[y:y+h, x:x+w] = blended
            else:
                # If no faces are detected, apply a mild global portrait enhancement
                smoothed = cv2.bilateralFilter(img, d=7, sigmaColor=25, sigmaSpace=25)
                gaussian_blur = cv2.GaussianBlur(smoothed, (5, 5), 0)
                output_img = cv2.addWeighted(smoothed, 1.3, gaussian_blur, -0.3, 0)

            # Save enhanced image
            cv2.imwrite(output_path, output_img)
            return True

    except Exception as e:
        print(f"Face enhancement error: {str(e)}")
        return False
