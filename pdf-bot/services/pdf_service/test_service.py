#!/usr/bin/env python3
"""
Test script for PDF Processing Microservice
"""

import requests
import sys
import time
from pathlib import Path

def test_health_check():
    """Test health check endpoints"""
    print("🏥 Testing health check endpoints...")
    
    try:
        # Test basic endpoint
        response = requests.get("http://localhost:8000/")
        if response.status_code == 200:
            print("  ✅ Basic endpoint working")
            data = response.json()
            print(f"     Service: {data.get('message')}")
            print(f"     Version: {data.get('version')}")
        else:
            print(f"  ❌ Basic endpoint failed: {response.status_code}")
            return False
            
        # Test health endpoint
        response = requests.get("http://localhost:8000/health")
        if response.status_code == 200:
            print("  ✅ Health endpoint working")
            data = response.json()
            services = data.get('services', {})
            for service, status in services.items():
                print(f"     {service}: {status}")
        else:
            print(f"  ❌ Health endpoint failed: {response.status_code}")
            return False
            
        return True
        
    except requests.exceptions.ConnectionError:
        print("  ❌ Service not running or not accessible")
        return False
    except Exception as e:
        print(f"  ❌ Health check failed: {e}")
        return False

def create_test_pdf():
    """Create a simple test PDF"""
    try:
        from reportlab.pdfgen import canvas
        from reportlab.lib.pagesizes import letter
        
        test_pdf_path = "test_document.pdf"
        c = canvas.Canvas(test_pdf_path, pagesize=letter)
        
        # Add some content
        c.drawString(100, 750, "Test PDF Document")
        c.drawString(100, 730, "This is a test document for the PDF processing service.")
        c.drawString(100, 710, "It contains sample text for testing OCR, summarization,")
        c.drawString(100, 690, "translation, and other PDF processing features.")
        
        # Add more content for better testing
        for i in range(10):
            c.drawString(100, 650 - i*20, f"Sample line {i+1} with some text content.")
        
        c.save()
        print(f"  ✅ Test PDF created: {test_pdf_path}")
        return test_pdf_path
        
    except ImportError:
        print("  ⚠️  ReportLab not available, using placeholder")
        # Create a minimal PDF placeholder
        test_pdf_path = "test_document.pdf"
        with open(test_pdf_path, 'wb') as f:
            # Minimal PDF structure
            f.write(b"%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n")
            f.write(b"2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n")
            f.write(b"3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>\nendobj\n")
            f.write(b"xref\n0 4\n0000000000 65535 f \n0000000010 00000 n \n0000000053 00000 n \n")
            f.write(b"0000000125 00000 n \ntrailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n")
            f.write(b"173\n%%EOF")
        return test_pdf_path

def test_endpoint(endpoint, files, data=None):
    """Test a specific endpoint"""
    try:
        print(f"    Testing {endpoint}...")
        
        response = requests.post(
            f"http://localhost:8000{endpoint}",
            files=files,
            data=data or {}
        )
        
        if response.status_code == 200:
            print(f"      ✅ {endpoint} working")
            
            # Check if response is a file
            content_type = response.headers.get('content-type', '')
            if 'application/' in content_type or 'text/' in content_type:
                print(f"      📄 Response type: {content_type}")
                print(f"      📏 Response size: {len(response.content)} bytes")
            
            return True
        else:
            print(f"      ❌ {endpoint} failed: {response.status_code}")
            try:
                error_detail = response.json().get('detail', 'Unknown error')
                print(f"         Error: {error_detail}")
            except:
                print(f"         Raw error: {response.text[:200]}")
            return False
            
    except Exception as e:
        print(f"      ❌ {endpoint} error: {e}")
        return False

def test_pdf_endpoints():
    """Test all PDF processing endpoints"""
    print("\n📄 Testing PDF processing endpoints...")
    
    # Create test PDF
    test_pdf_path = create_test_pdf()
    
    if not Path(test_pdf_path).exists():
        print("  ❌ Test PDF not found")
        return False
    
    try:
        with open(test_pdf_path, 'rb') as pdf_file:
            pdf_content = pdf_file.read()
        
        results = []
        
        # Test compression
        files = {'file': ('test.pdf', pdf_content, 'application/pdf')}
        data = {'mode': 'whatsapp', 'quality': 'medium'}
        results.append(test_endpoint('/compress', files, data))
        
        # Test conversion
        files = {'file': ('test.pdf', pdf_content, 'application/pdf')}
        data = {'format': 'docx'}
        results.append(test_endpoint('/convert', files, data))
        
        # Test OCR
        files = {'file': ('test.pdf', pdf_content, 'application/pdf')}
        data = {'language': 'eng', 'output_format': 'txt'}
        results.append(test_endpoint('/ocr', files, data))
        
        # Test summarization
        files = {'file': ('test.pdf', pdf_content, 'application/pdf')}
        data = {'length': 'medium', 'language': 'en'}
        results.append(test_endpoint('/summarize', files, data))
        
        # Test translation
        files = {'file': ('test.pdf', pdf_content, 'application/pdf')}
        data = {'target_language': 'fr', 'source_language': 'auto', 'output_format': 'txt'}
        results.append(test_endpoint('/translate', files, data))
        
        # Test security
        files = {'file': ('test.pdf', pdf_content, 'application/pdf')}
        data = {'action': 'password', 'password': 'testpass123'}
        results.append(test_endpoint('/secure', files, data))
        
        # Clean up
        try:
            Path(test_pdf_path).unlink()
        except:
            pass
        
        success_count = sum(results)
        total_count = len(results)
        
        print(f"\n📊 PDF Endpoints Test Results: {success_count}/{total_count} passed")
        
        return success_count == total_count
        
    except Exception as e:
        print(f"  ❌ PDF endpoint testing failed: {e}")
        return False

def main():
    """Main test function"""
    print("🧪 PDF Processing Microservice Test Suite")
    print("=" * 50)
    
    # Wait for service to be ready
    print("⏳ Waiting for service to be ready...")
    time.sleep(2)
    
    tests_passed = 0
    total_tests = 2
    
    # Test health checks
    if test_health_check():
        tests_passed += 1
    
    # Test PDF endpoints
    if test_pdf_endpoints():
        tests_passed += 1
    
    print("\n" + "=" * 50)
    print(f"🏁 Test Results: {tests_passed}/{total_tests} test suites passed")
    
    if tests_passed == total_tests:
        print("✅ All tests passed! Service is working correctly.")
        return 0
    else:
        print("❌ Some tests failed. Check the service configuration.")
        return 1

if __name__ == "__main__":
    exit_code = main()
    sys.exit(exit_code)
