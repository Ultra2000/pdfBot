import pdfminer.high_level
import tempfile
import os
import logging
from pathlib import Path
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import letter
from reportlab.lib.styles import getSampleStyleSheet
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer
from utils.response_utils import create_temp_response_file, create_temp_binary_file

logger = logging.getLogger(__name__)

class TranslateService:
    """
    Service for PDF content translation
    """
    
    def __init__(self):
        self.supported_languages = {
            'en': 'English',
            'fr': 'French',
            'es': 'Spanish', 
            'de': 'German',
            'it': 'Italian',
            'pt': 'Portuguese',
            'ru': 'Russian',
            'zh': 'Chinese',
            'ja': 'Japanese',
            'ko': 'Korean',
            'ar': 'Arabic',
            'hi': 'Hindi'
        }
    
    async def translate(self, input_path: str, target_language: str, source_language: str = "auto", output_format: str = "txt") -> str:
        """
        Translate PDF content
        
        Args:
            input_path: Path to input PDF
            target_language: Target language code
            source_language: Source language code (auto for auto-detect)
            output_format: Output format (txt/pdf)
            
        Returns:
            Path to translated file
        """
        try:
            logger.info(f"Translating PDF: {source_language} -> {target_language}, format={output_format}")
            
            # Validate target language
            if target_language not in self.supported_languages:
                raise ValueError(f"Unsupported target language: {target_language}")
            
            # Extract text from PDF
            try:
                text = pdfminer.high_level.extract_text(input_path)
                if not text.strip():
                    raise ValueError("No text could be extracted from PDF")
            except Exception as e:
                logger.error(f"Text extraction failed: {e}")
                return self._create_placeholder_result(input_path, target_language, source_language, output_format)
            
            # Clean text
            cleaned_text = self._clean_text(text)
            
            # Translate text (placeholder implementation)
            translated_text = self._translate_text(cleaned_text, target_language, source_language)
            
            # Create output based on format
            if output_format == "pdf":
                return self._create_pdf_output(translated_text, input_path, target_language)
            else:
                return self._create_text_output(translated_text, input_path, target_language)
                
        except Exception as e:
            logger.error(f"Translation failed: {e}")
            return self._create_placeholder_result(input_path, target_language, source_language, output_format)
    
    def _clean_text(self, text: str) -> str:
        """Clean extracted text for translation"""
        try:
            # Remove excessive whitespace
            cleaned = " ".join(text.split())
            
            # Remove common PDF artifacts
            cleaned = cleaned.replace("\x0c", " ")  # Form feed
            cleaned = cleaned.replace("\u2022", "•")  # Bullet points
            
            # Split into chunks for translation (max 5000 chars per chunk)
            chunks = []
            current_chunk = ""
            
            sentences = cleaned.split('. ')
            for sentence in sentences:
                if len(current_chunk + sentence) < 4000:
                    current_chunk += sentence + ". "
                else:
                    if current_chunk:
                        chunks.append(current_chunk.strip())
                    current_chunk = sentence + ". "
            
            if current_chunk:
                chunks.append(current_chunk.strip())
                
            return chunks if len(chunks) > 1 else [cleaned]
            
        except Exception as e:
            logger.error(f"Text cleaning failed: {e}")
            return [text]
    
    def _translate_text(self, text_chunks: list, target_language: str, source_language: str) -> str:
        """
        Translate text chunks (placeholder implementation)
        
        In a full implementation, this would use:
        - Google Translate API
        - DeepL API
        - Azure Translator
        - AWS Translate
        - Or other translation services
        """
        try:
            target_lang_name = self.supported_languages.get(target_language, target_language)
            
            # Placeholder translation logic
            translated_chunks = []
            
            for i, chunk in enumerate(text_chunks):
                if isinstance(text_chunks, str):
                    chunk = text_chunks
                    
                # Simulate translation with placeholder text
                translated_chunk = f"""[TRANSLATED TO {target_lang_name.upper()}]

{chunk}

[END TRANSLATION CHUNK {i + 1}]"""
                
                translated_chunks.append(translated_chunk)
                
                if isinstance(text_chunks, str):
                    break
            
            translated_text = "\n\n".join(translated_chunks)
            
            # Add translation metadata
            translation_with_metadata = f"""DOCUMENT TRANSLATION

Source Language: {source_language}
Target Language: {target_language} ({target_lang_name})
Translation Method: Placeholder (production would use AI translation)
Original Length: {len(str(text_chunks))} characters
Translated Length: {len(translated_text)} characters

TRANSLATED CONTENT:
{translated_text}

---
Note: This is a placeholder translation. In production, this would use
advanced translation services like Google Translate, DeepL, or Azure Translator
for accurate, context-aware translations.
"""
            
            return translation_with_metadata
            
        except Exception as e:
            logger.error(f"Translation generation failed: {e}")
            return f"Translation failed: {str(e)}"
    
    def _create_text_output(self, translated_text: str, input_path: str, target_language: str) -> str:
        """Create text file output"""
        try:
            # Add header information
            header = f"PDF Translation to {self.supported_languages.get(target_language, target_language)}\n"
            header += f"Source: {Path(input_path).name}\n"
            header += f"Translated: {os.path.getctime(input_path)}\n"
            header += "=" * 50 + "\n\n"
            
            full_content = header + translated_text
            
            return create_temp_response_file(full_content, "txt")
            
        except Exception as e:
            logger.error(f"Failed to create text output: {e}")
            raise
    
    def _create_pdf_output(self, translated_text: str, input_path: str, target_language: str) -> str:
        """Create PDF file output"""
        try:
            output_path = create_temp_binary_file(b"", "pdf")
            
            # Create PDF document
            doc = SimpleDocTemplate(
                output_path,
                pagesize=letter,
                topMargin=72,
                bottomMargin=72,
                leftMargin=72,
                rightMargin=72
            )
            
            # Get styles
            styles = getSampleStyleSheet()
            title_style = styles['Title']
            normal_style = styles['Normal']
            
            # Build content
            story = []
            
            # Title
            title = Paragraph(f"Translated Document ({self.supported_languages.get(target_language, target_language)})", title_style)
            story.append(title)
            story.append(Spacer(1, 20))
            
            # Metadata
            metadata = f"Source: {Path(input_path).name}"
            story.append(Paragraph(metadata, normal_style))
            story.append(Spacer(1, 20))
            
            # Translated content
            # Split into paragraphs for better formatting
            paragraphs = translated_text.split('\n\n')
            for para in paragraphs:
                if para.strip():
                    p = Paragraph(para.strip(), normal_style)
                    story.append(p)
                    story.append(Spacer(1, 12))
            
            # Build PDF
            doc.build(story)
            
            return output_path
            
        except Exception as e:
            logger.error(f"Failed to create PDF output: {e}")
            # Fallback to text output
            return self._create_text_output(translated_text, input_path, target_language)
    
    def _create_placeholder_result(self, input_path: str, target_language: str, source_language: str, output_format: str) -> str:
        """Create placeholder result for testing"""
        try:
            target_lang_name = self.supported_languages.get(target_language, target_language)
            
            placeholder_translation = f"""PDF TRANSLATION - PLACEHOLDER RESULT

Source File: {Path(input_path).name}
Source Language: {source_language}
Target Language: {target_language} ({target_lang_name})
Output Format: {output_format.upper()}
Processing Status: Placeholder (actual AI translation not available)

TRANSLATED CONTENT:
This is a placeholder translation result for testing purposes. In a full
implementation, this would contain the actual translated content from the
PDF using professional translation services.

The translation would preserve:
- Document structure and formatting
- Technical terminology accuracy
- Cultural context and nuances
- Professional language standards

TRANSLATION FEATURES:
- High-quality neural machine translation
- Context-aware translations
- Support for {len(self.supported_languages)} languages
- Batch processing for large documents
- Quality assurance and post-editing

SUPPORTED SERVICES:
- Google Cloud Translation API
- DeepL Professional API
- Azure Translator Text API
- AWS Amazon Translate
- Custom translation models

---
Note: To enable full translation functionality, integrate with professional
translation APIs and configure proper authentication credentials.
"""
            
            if output_format == "pdf":
                # Create simple PDF with placeholder text
                output_path = create_temp_binary_file(b"", "pdf")
                
                doc = SimpleDocTemplate(output_path, pagesize=letter)
                styles = getSampleStyleSheet()
                story = []
                
                title = Paragraph("Translation Placeholder", styles['Title'])
                content = Paragraph(placeholder_translation, styles['Normal'])
                
                story.extend([title, Spacer(1, 20), content])
                doc.build(story)
                
                return output_path
            else:
                return create_temp_response_file(placeholder_translation, "txt")
                
        except Exception as e:
            logger.error(f"Failed to create placeholder result: {e}")
            raise
