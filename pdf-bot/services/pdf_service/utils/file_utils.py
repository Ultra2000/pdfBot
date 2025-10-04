from fastapi import UploadFile
from fastapi.responses import FileResponse
import tempfile
import os
import shutil
from pathlib import Path
from contextlib import contextmanager
import logging

logger = logging.getLogger(__name__)

def validate_pdf(file: UploadFile) -> bool:
    """
    Validate that the uploaded file is a PDF
    """
    try:
        # Check file extension
        if not file.filename.lower().endswith('.pdf'):
            return False
            
        # Check MIME type
        if file.content_type and not file.content_type.startswith('application/pdf'):
            return False
            
        # Check file signature (PDF magic bytes)
        file.file.seek(0)
        header = file.file.read(4)
        file.file.seek(0)  # Reset position
        
        return header == b'%PDF'
        
    except Exception as e:
        logger.error(f"PDF validation error: {e}")
        return False

@contextmanager
def create_temp_file(upload_file: UploadFile):
    """
    Context manager to create a temporary file from UploadFile
    """
    temp_file = None
    try:
        # Create temporary file
        temp_file = tempfile.NamedTemporaryFile(delete=False, suffix='.pdf')
        
        # Copy uploaded file content to temp file
        shutil.copyfileobj(upload_file.file, temp_file)
        temp_file.close()
        
        # Reset upload file position
        upload_file.file.seek(0)
        
        yield temp_file.name
        
    finally:
        # Clean up temporary file
        if temp_file and os.path.exists(temp_file.name):
            try:
                os.unlink(temp_file.name)
            except Exception as e:
                logger.warning(f"Failed to delete temp file {temp_file.name}: {e}")

def create_temp_dir() -> str:
    """
    Create a temporary directory
    """
    return tempfile.mkdtemp()

def cleanup_temp_dir(temp_dir: str):
    """
    Clean up temporary directory
    """
    try:
        if os.path.exists(temp_dir):
            shutil.rmtree(temp_dir)
    except Exception as e:
        logger.warning(f"Failed to cleanup temp directory {temp_dir}: {e}")

def get_file_size(file_path: str) -> int:
    """
    Get file size in bytes
    """
    return os.path.getsize(file_path)

def ensure_file_extension(file_path: str, extension: str) -> str:
    """
    Ensure file has the correct extension
    """
    path = Path(file_path)
    if not path.suffix == f'.{extension}':
        new_path = path.with_suffix(f'.{extension}')
        return str(new_path)
    return file_path
