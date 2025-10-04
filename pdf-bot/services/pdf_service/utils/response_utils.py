from fastapi.responses import FileResponse
import tempfile
import os
import logging

logger = logging.getLogger(__name__)

def create_file_response(file_path: str, filename: str, media_type: str) -> FileResponse:
    """
    Create a FileResponse with proper headers and cleanup
    """
    try:
        # Ensure file exists
        if not os.path.exists(file_path):
            raise FileNotFoundError(f"Result file not found: {file_path}")
            
        # Create response with custom filename
        response = FileResponse(
            path=file_path,
            media_type=media_type,
            filename=filename
        )
        
        # Add headers for better client handling
        response.headers["Content-Disposition"] = f"attachment; filename=\"{filename}\""
        response.headers["Cache-Control"] = "no-cache, no-store, must-revalidate"
        response.headers["Pragma"] = "no-cache"
        response.headers["Expires"] = "0"
        
        return response
        
    except Exception as e:
        logger.error(f"Error creating file response: {e}")
        raise

def create_temp_response_file(content: str, extension: str = "txt") -> str:
    """
    Create a temporary file with content for response
    """
    temp_file = tempfile.NamedTemporaryFile(
        mode='w', 
        suffix=f'.{extension}',
        delete=False,
        encoding='utf-8'
    )
    
    try:
        temp_file.write(content)
        temp_file.flush()
        return temp_file.name
    finally:
        temp_file.close()

def create_temp_binary_file(content: bytes, extension: str = "pdf") -> str:
    """
    Create a temporary file with binary content for response
    """
    temp_file = tempfile.NamedTemporaryFile(
        mode='wb',
        suffix=f'.{extension}',
        delete=False
    )
    
    try:
        temp_file.write(content)
        temp_file.flush()
        return temp_file.name
    finally:
        temp_file.close()
