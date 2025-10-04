# PDF Processing Microservice

FastAPI microservice for PDF processing operations including compression, conversion, OCR, summarization, translation, and security features.

## Features

- **Compression**: Reduce PDF file size with multiple quality modes
- **Conversion**: Convert PDF to DOCX, XLSX, or image formats
- **OCR**: Extract text from scanned PDFs using Tesseract
- **Summarization**: Generate content summaries (placeholder for AI integration)
- **Translation**: Translate PDF content to different languages (placeholder for AI integration)
- **Security**: Add password protection and watermarks

## Requirements

- Python 3.11+
- FastAPI
- Tesseract OCR (for text extraction)
- Poppler (for PDF to image conversion)

## Installation

### Local Development

1. Create virtual environment:
```bash
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
```

2. Install dependencies:
```bash
pip install -r requirements.txt
```

3. Install system dependencies:
   - **Tesseract OCR**: Follow installation guide for your OS
   - **Poppler**: Required for pdf2image

4. Run the service:
```bash
uvicorn main:app --host 0.0.0.0 --port 8000 --reload
```

### Docker

1. Build the image:
```bash
docker build -t pdf-service .
```

2. Run the container:
```bash
docker run -p 8000:8000 pdf-service
```

## API Endpoints

### Health Check
- `GET /` - Basic service info
- `GET /health` - Detailed health check

### PDF Operations
- `POST /compress` - Compress PDF files
- `POST /convert` - Convert PDF to other formats
- `POST /ocr` - Extract text using OCR
- `POST /summarize` - Summarize PDF content
- `POST /translate` - Translate PDF content
- `POST /secure` - Add password/watermark protection

## Usage Examples

### Compress PDF
```bash
curl -X POST "http://localhost:8000/compress" \
  -H "Content-Type: multipart/form-data" \
  -F "file=@document.pdf" \
  -F "mode=whatsapp" \
  -F "quality=medium"
```

### Convert to DOCX
```bash
curl -X POST "http://localhost:8000/convert" \
  -H "Content-Type: multipart/form-data" \
  -F "file=@document.pdf" \
  -F "format=docx"
```

### Extract Text (OCR)
```bash
curl -X POST "http://localhost:8000/ocr" \
  -H "Content-Type: multipart/form-data" \
  -F "file=@document.pdf" \
  -F "language=eng" \
  -F "output_format=txt"
```

### Summarize Content
```bash
curl -X POST "http://localhost:8000/summarize" \
  -H "Content-Type: multipart/form-data" \
  -F "file=@document.pdf" \
  -F "length=medium" \
  -F "language=en"
```

### Translate Content
```bash
curl -X POST "http://localhost:8000/translate" \
  -H "Content-Type: multipart/form-data" \
  -F "file=@document.pdf" \
  -F "target_language=fr" \
  -F "source_language=auto" \
  -F "output_format=txt"
```

### Secure PDF
```bash
curl -X POST "http://localhost:8000/secure" \
  -H "Content-Type: multipart/form-data" \
  -F "file=@document.pdf" \
  -F "action=password" \
  -F "password=mysecretpassword"
```

## Configuration

The service uses environment variables for configuration:

```bash
# Server settings
HOST=0.0.0.0
PORT=8000
DEBUG=False

# Processing limits
MAX_FILE_SIZE=50MB
TEMP_DIR=/tmp/pdf_processing

# OCR settings
TESSERACT_CMD=/usr/bin/tesseract
DEFAULT_OCR_LANGUAGE=eng

# External API keys (for production)
OPENAI_API_KEY=your_openai_key
DEEPL_API_KEY=your_deepl_key
GOOGLE_TRANSLATE_KEY=your_google_key
```

## Development Notes

### Current Implementation Status

- ✅ **Compression**: Functional with pikepdf
- ✅ **Conversion**: Basic implementation with pdf2image
- ✅ **OCR**: Ready for Tesseract integration
- 🔄 **Summarization**: Placeholder (needs AI integration)
- 🔄 **Translation**: Placeholder (needs translation API)
- ✅ **Security**: Basic password protection

### Production Enhancements

For production deployment:

1. **AI Integration**: 
   - OpenAI GPT for summarization
   - Google Translate/DeepL for translation

2. **Performance**:
   - Redis caching
   - Async processing
   - Load balancing

3. **Security**:
   - Input validation
   - Rate limiting
   - Authentication

4. **Monitoring**:
   - Logging
   - Metrics
   - Health checks

## Integration with Laravel

This microservice is designed to work with the Laravel WhatsApp PDF bot. The Laravel application calls these endpoints via HTTP requests to process PDF files received through WhatsApp.

### Laravel Integration Example

```php
// In Laravel job class
$response = Http::attach(
    'file', $pdfContent, 'document.pdf'
)->post('http://pdf-service:8000/compress', [
    'mode' => 'whatsapp',
    'quality' => 'medium'
]);
```

## Troubleshooting

### Common Issues

1. **Tesseract not found**: Ensure Tesseract is installed and in PATH
2. **Poppler not found**: Install poppler-utils package
3. **Memory errors**: Increase container memory limits
4. **File size limits**: Check MAX_FILE_SIZE configuration

### Logs

Check service logs for detailed error information:
```bash
docker logs pdf-service
```

## License

This project is part of the WhatsApp PDF Bot system.
