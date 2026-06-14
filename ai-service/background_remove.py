import os
import cv2
from rembg import remove

from utils import load_cv2_image

def remove_background(input_path: str, output_path: str) -> bool:
    """
    Remove background from image using rembg and save as transparent PNG.
    """
    if not os.path.exists(input_path):
        return False
        
    try:
        # Read image
        img = load_cv2_image(input_path, cv2.IMREAD_UNCHANGED)
        if img is None:
            return False
            
        # Apply rembg
        # It handles 3-channel (BGR) and 4-channel (BGRA) inputs automatically
        output = remove(img)
        
        # Save output image (forcing .png extension to support transparency)
        cv2.imwrite(output_path, output)
        return True
        
    except Exception as e:
        print(f"Background removal error: {str(e)}")
        return False
