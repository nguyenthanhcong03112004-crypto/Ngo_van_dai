import zipfile
import xml.etree.ElementTree as ET
import sys
import os

try:
    sys.stdout.reconfigure(encoding='utf-8')
except Exception:
    pass

def extract_text_from_docx(docx_path):
    ns = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
    text = []
    
    with zipfile.ZipFile(docx_path) as docx:
        tree = ET.parse(docx.open('word/document.xml'))
        root = tree.getroot()
        
        for paragraph in root.iter(f"{{{ns['w']}}}p"):
            para_text = []
            for run in paragraph.iter(f"{{{ns['w']}}}r"):
                for text_node in run.iter(f"{{{ns['w']}}}t"):
                    if text_node.text:
                        para_text.append(text_node.text)
            if para_text:
                text.append(''.join(para_text))
            else:
                text.append('')
                
    return '\n'.join(text)

def main():
    for docx_path in sys.argv[1:]:
        txt_path = os.path.splitext(docx_path)[0] + '.txt'
        try:
            content = extract_text_from_docx(docx_path)
            with open(txt_path, 'w', encoding='utf-8') as f:
                f.write(content)
        except Exception as e:
            pass

if __name__ == '__main__':
    main()
