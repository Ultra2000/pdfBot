from fastapi import FastAPI, File, UploadFile, Form, HTTPException
from fastapi.responses import FileResponse
from pydantic import BaseModel
from typing import Optional, Dict, Any
import tempfile
import os
import shutil
import logging
from pathlib import Path

# Import our processing modules
from services.compress_service import CompressService
from services.convert_service import ConvertService
from services.ocr_service import OcrService
from services.summarize_service import SummarizeService
from services.translate_service import TranslateService
from services.secure_service import SecureService
from utils.file_utils import validate_pdf, create_temp_file
from utils.response_utils import create_file_response

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(
    title="PDF Processing Microservice",
    description="FastAPI microservice for PDF processing operations",
    version="1.0.0"
)

# Initialize services
compress_service = CompressService()
convert_service = ConvertService()
ocr_service = OcrService()
summarize_service = SummarizeService()
translate_service = TranslateService()
secure_service = SecureService()

@app.get("/")
async def root():
    """Health check endpoint"""
    return {
        "message": "PDF Processing Microservice",
        "status": "healthy",
        "version": "1.0.0",
        "endpoints": [
            "/compress",
            "/convert", 
            "/ocr",
            "/summarize",
            "/translate",
            "/secure"
        ]
    }

@app.get("/health")
async def health_check():
    """Detailed health check"""
    return {
        "status": "healthy",
        "services": {
            "compress": "available",
            "convert": "available",
            "ocr": "available", 
            "summarize": "available",
            "translate": "available",
            "secure": "available"
        }
    }

@app.post("/compress")
async def compress_pdf(
    file: UploadFile = File(...),
    mode: str = Form(default="whatsapp"),  # whatsapp/print/balanced
    quality: str = Form(default="medium")   # low/medium/high
):
    """
    Compress PDF file
    
    Parameters:
    - file: PDF file to compress
    - mode: Compression mode (whatsapp/print/balanced)
    - quality: Quality level (low/medium/high)
    """
    try:
        logger.info(f"Compressing PDF: mode={mode}, quality={quality}")
        
        # Validate input
        if not validate_pdf(file):
            raise HTTPException(status_code=400, detail="Invalid PDF file")
        
        # Process file
        with create_temp_file(file) as temp_input:
            result_path = await compress_service.compress(
                temp_input, 
                mode=mode, 
                quality=quality
            )
            
            return create_file_response(
                result_path,
                filename=f"compressed_{file.filename}",
                media_type="application/pdf"
            )
            
    except Exception as e:
        logger.error(f"Compression error: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Compression failed: {str(e)}")

@app.post("/convert")
async def convert_pdf(
    file: UploadFile = File(...),
    format: str = Form(...),  # docx/xlsx/img
    options: Optional[str] = Form(default=None)  # JSON string with additional options
):
    """
    Convert PDF to other formats
    
    Parameters:
    - file: PDF file to convert
    - format: Target format (docx/xlsx/img)
    - options: Additional conversion options (JSON string)
    """
    try:
        logger.info(f"Converting PDF to {format}")
        
        if not validate_pdf(file):
            raise HTTPException(status_code=400, detail="Invalid PDF file")
            
        if format not in ["docx", "xlsx", "img", "png", "jpg", "jpeg"]:
            raise HTTPException(status_code=400, detail="Unsupported format")
        
        with create_temp_file(file) as temp_input:
            result_path = await convert_service.convert(
                temp_input,
                target_format=format,
                options=options
            )
            
            # Determine media type based on format
            media_types = {
                "docx": "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                "xlsx": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", 
                "img": "image/png",
                "png": "image/png",
                "jpg": "image/jpeg",
                "jpeg": "image/jpeg"
            }
            
            return create_file_response(
                result_path,
                filename=f"converted_{Path(file.filename).stem}.{format}",
                media_type=media_types.get(format, "application/octet-stream")
            )
            
    except Exception as e:
        logger.error(f"Conversion error: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Conversion failed: {str(e)}")

@app.post("/ocr")
async def extract_text_ocr(
    file: UploadFile = File(...),
    language: str = Form(default="eng"),  # OCR language
    output_format: str = Form(default="txt")  # txt/docx
):
    """
    Extract text from PDF using OCR
    
    Parameters:
    - file: PDF file for OCR
    - language: OCR language (eng/fra/etc.)
    - output_format: Output format (txt/docx)
    """
    try:
        logger.info(f"OCR processing: language={language}, format={output_format}")
        
        if not validate_pdf(file):
            raise HTTPException(status_code=400, detail="Invalid PDF file")
            
        with create_temp_file(file) as temp_input:
            result_path = await ocr_service.extract_text(
                temp_input,
                language=language,
                output_format=output_format
            )
            
            media_type = "text/plain" if output_format == "txt" else "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
            
            return create_file_response(
                result_path,
                filename=f"ocr_{Path(file.filename).stem}.{output_format}",
                media_type=media_type
            )
            
    except Exception as e:
        logger.error(f"OCR error: {str(e)}")
        raise HTTPException(status_code=500, detail=f"OCR failed: {str(e)}")

@app.post("/summarize")
async def summarize_pdf(
    file: UploadFile = File(...),
    length: str = Form(default="medium"),  # short/medium/long
    language: str = Form(default="en")     # output language
):
    """
    Summarize PDF content
    
    Parameters:
    - file: PDF file to summarize
    - length: Summary length (short/medium/long)
    - language: Output language (en/fr/etc.)
    """
    try:
        logger.info(f"Summarizing PDF: length={length}, language={language}")
        
        if not validate_pdf(file):
            raise HTTPException(status_code=400, detail="Invalid PDF file")
            
        with create_temp_file(file) as temp_input:
            result_path = await summarize_service.summarize(
                temp_input,
                length=length,
                language=language
            )
            
            return create_file_response(
                result_path,
                filename=f"summary_{Path(file.filename).stem}.txt",
                media_type="text/plain"
            )
            
    except Exception as e:
        logger.error(f"Summarization error: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Summarization failed: {str(e)}")

@app.post("/translate")
async def translate_pdf(
    file: UploadFile = File(...),
    target_language: str = Form(...),      # Target language code (fr/en/es/etc.)
    source_language: str = Form(default="auto"),  # Source language (auto-detect)
    output_format: str = Form(default="txt")      # txt/pdf
):
    """
    Translate PDF content
    
    Parameters:
    - file: PDF file to translate
    - target_language: Target language code
    - source_language: Source language (auto for auto-detect)
    - output_format: Output format (txt/pdf)
    """
    try:
        logger.info(f"Translating PDF: {source_language} -> {target_language}, format={output_format}")
        
        if not validate_pdf(file):
            raise HTTPException(status_code=400, detail="Invalid PDF file")
            
        with create_temp_file(file) as temp_input:
            result_path = await translate_service.translate(
                temp_input,
                target_language=target_language,
                source_language=source_language,
                output_format=output_format
            )
            
            media_type = "text/plain" if output_format == "txt" else "application/pdf"
            extension = output_format if output_format in ["txt", "pdf"] else "txt"
            
            return create_file_response(
                result_path,
                filename=f"translated_{Path(file.filename).stem}.{extension}",
                media_type=media_type
            )
            
    except Exception as e:
        logger.error(f"Translation error: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Translation failed: {str(e)}")

@app.post("/secure")
async def secure_pdf(
    file: UploadFile = File(...),
    action: str = Form(...),  # password/watermark/both
    password: Optional[str] = Form(default=None),
    watermark_text: Optional[str] = Form(default=None),
    watermark_position: str = Form(default="center")  # center/corner/diagonal
):
    """
    Secure PDF with password and/or watermark
    
    Parameters:
    - file: PDF file to secure
    - action: Security action (password/watermark/both)
    - password: Password for encryption
    - watermark_text: Text for watermark
    - watermark_position: Watermark position
    """
    try:
        logger.info(f"Securing PDF: action={action}")
        
        if not validate_pdf(file):
            raise HTTPException(status_code=400, detail="Invalid PDF file")
            
        if action in ["password", "both"] and not password:
            raise HTTPException(status_code=400, detail="Password required for password protection")
            
        if action in ["watermark", "both"] and not watermark_text:
            raise HTTPException(status_code=400, detail="Watermark text required for watermark")
            
        with create_temp_file(file) as temp_input:
            result_path = await secure_service.secure(
                temp_input,
                action=action,
                password=password,
                watermark_text=watermark_text,
                watermark_position=watermark_position
            )
            
            return create_file_response(
                result_path,
                filename=f"secured_{file.filename}",
                media_type="application/pdf"
            )
            
    except Exception as e:
        logger.error(f"Security error: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Security operation failed: {str(e)}")

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
