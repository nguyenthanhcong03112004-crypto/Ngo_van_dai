const fs = require('fs');
const path = require('path');

function walkDir(dir, callback) {
  fs.readdirSync(dir).forEach(f => {
    let dirPath = path.join(dir, f);
    let isDirectory = fs.statSync(dirPath).isDirectory();
    isDirectory ? walkDir(dirPath, callback) : callback(dirPath);
  });
}

const frontendDir = 'xampp/htdocs/frontend';
let fixedCount = 0;
walkDir(frontendDir, (filePath) => {
  if (filePath.endsWith('.html') || filePath.endsWith('.js')) {
    let content = fs.readFileSync(filePath, 'utf8');
    let original = content;
    
    // Fix pattern where Powershell inserted '`${window.API_BASE} -> `${window.API_BASE}
    // E.g. '`${window.API_BASE}/api/admin/products' -> `${window.API_BASE}/api/admin/products`
    content = content.replace(/'`\$\{window\.API_BASE\}([^']*)'/g, '`${window.API_BASE}$1`');
    
    // Fix double backticks: ``${window.API_BASE} -> `${window.API_BASE}
    content = content.replace(/``\$\{window\.API_BASE\}/g, '`${window.API_BASE}');

    if (content !== original) {
      fs.writeFileSync(filePath, content, 'utf8');
      console.log('Fixed syntax in:', filePath);
      fixedCount++;
    }
  }
});
console.log('Total files fixed:', fixedCount);
