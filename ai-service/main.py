import os
import shutil
import uuid
from fastapi import FastAPI, UploadFile, File, Form, HTTPException
from fastapi.responses import FileResponse, JSONResponse

# Import processors
from upscale import upscale_image
from face_enhance import enhance_faces
from background_remove import remove_background
from quality_score import analyze_image_quality
from tagger import generate_image_tags

app = FastAPI(title="ImageLab AI Microservice", version="1.0")

# Setup temp directory inside the service folder
TEMP_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "temp")
os.makedirs(TEMP_DIR, exist_ok=True)

def get_temp_paths(ext: str):
    """Generate secure temporary input and output file paths."""
    token = str(uuid.uuid4())
    input_path = os.path.join(TEMP_DIR, f"in_{token}.{ext}")
    output_path = os.path.join(TEMP_DIR, f"out_{token}.{ext}")
    return input_path, output_path

def cleanup_files(*paths):
    """Safely delete temporary files."""
    for path in paths:
        if path and os.path.exists(path):
            try:
                os.remove(path)
            except Exception as e:
                print(f"Failed to remove temp file {path}: {str(e)}")

@app.post("/upscale")
async def api_upscale(image: UploadFile = File(...), scale: int = Form(2)):
    if scale not in [2, 4]:
        raise HTTPException(status_code=400, detail="Scale factor must be 2 or 4.")
        
    ext = image.filename.split(".")[-1] if "." in image.filename else "jpg"
    input_path, output_path = get_temp_paths(ext)
    
    try:
        # Save uploaded file
        with open(input_path, "wb") as buffer:
            shutil.copyfileobj(image.file, buffer)
            
        # Run upscale
        success = upscale_image(input_path, output_path, scale)
        if not success or not os.path.exists(output_path):
            raise HTTPException(status_code=500, detail="Upscaling failed.")
            
        # Return file response
        # Since FastAPI sends file asynchronously, we must delete the input file now,
        # and delete the output file *after* sending it. FileResponse handles this cleanly on delete or we can clean it up after response.
        # But wait: to prevent lock, we delete input_path now, and return FileResponse.
        # We can clean up the temp output files periodically or clean it up in background tasks.
        cleanup_files(input_path)
        return FileResponse(output_path, media_type=f"image/{ext.lower()}", filename=f"upscaled_{scale}x.{ext}")
        
    except Exception as e:
        cleanup_files(input_path, output_path)
        return JSONResponse(status_code=500, content={"success": False, "error": str(e)})

@app.post("/face-enhance")
async def api_face_enhance(image: UploadFile = File(...)):
    ext = image.filename.split(".")[-1] if "." in image.filename else "jpg"
    input_path, output_path = get_temp_paths(ext)
    
    try:
        with open(input_path, "wb") as buffer:
            shutil.copyfileobj(image.file, buffer)
            
        success = enhance_faces(input_path, output_path)
        if not success or not os.path.exists(output_path):
            raise HTTPException(status_code=500, detail="Face enhancement failed.")
            
        cleanup_files(input_path)
        return FileResponse(output_path, media_type=f"image/{ext.lower()}", filename=f"face_enhanced.{ext}")
        
    except Exception as e:
        cleanup_files(input_path, output_path)
        return JSONResponse(status_code=500, content={"success": False, "error": str(e)})

@app.post("/remove-background")
async def api_remove_background(image: UploadFile = File(...)):
    # Output must be transparent PNG
    ext = image.filename.split(".")[-1] if "." in image.filename else "jpg"
    input_path, output_path = get_temp_paths(ext)
    # Force output path to be .png
    output_path = output_path.rsplit(".", 1)[0] + ".png"
    
    try:
        with open(input_path, "wb") as buffer:
            shutil.copyfileobj(image.file, buffer)
            
        success = remove_background(input_path, output_path)
        if not success or not os.path.exists(output_path):
            raise HTTPException(status_code=500, detail="Background removal failed.")
            
        cleanup_files(input_path)
        return FileResponse(output_path, media_type="image/png", filename="removed_bg.png")
        
    except Exception as e:
        cleanup_files(input_path, output_path)
        return JSONResponse(status_code=500, content={"success": False, "error": str(e)})

@app.post("/analyze-quality")
async def api_analyze_quality(image: UploadFile = File(...)):
    ext = image.filename.split(".")[-1] if "." in image.filename else "jpg"
    input_path, _ = get_temp_paths(ext)
    
    try:
        with open(input_path, "wb") as buffer:
            shutil.copyfileobj(image.file, buffer)
            
        result = analyze_image_quality(input_path)
        cleanup_files(input_path)
        return JSONResponse(content=result)
        
    except Exception as e:
        cleanup_files(input_path)
        return JSONResponse(status_code=500, content={"success": False, "error": str(e)})

@app.post("/generate-tags")
async def api_generate_tags(image: UploadFile = File(...)):
    ext = image.filename.split(".")[-1] if "." in image.filename else "jpg"
    input_path, _ = get_temp_paths(ext)
    
    try:
        with open(input_path, "wb") as buffer:
            shutil.copyfileobj(image.file, buffer)
            
        tags = generate_image_tags(input_path)
        cleanup_files(input_path)
        return JSONResponse(content={"success": True, "tags": tags})
        
    except Exception as e:
        cleanup_files(input_path)
        return JSONResponse(status_code=500, content={"success": False, "error": str(e)})

@app.get("/health")
async def health():
    return {"status": "online"}
