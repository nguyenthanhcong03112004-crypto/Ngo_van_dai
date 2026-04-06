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
walkDir(frontendDir, (filePath) => {
  if (filePath.endsWith('.html') || filePath.endsWith('.js')) {
    let content = fs.readFileSync(filePath, 'utf8');
    let original = content;
    
    // Fix pattern 1: '`${window.API_BASE}... ' -> `${window.API_BASE}...`
    // Basically changing single quotes ' to backticks ` when they wrap `${window.API_BASE}`
    content = content.replace(/'\$\{window\.API_BASE\}([^']*)'/g, '`${window.API_BASE}$1`');
    
    // Fix double quotes
    content = content.replace(/\"\$\{window\.API_BASE\}([^\"]*)\"/g, '`${window.API_BASE}$1`');
    
    // Fix pattern 2: ``${window.API_BASE} (two backticks at start)
    content = content.replace(/``\$\{window\.API_BASE\}/g, '`${window.API_BASE}');
    
    // URL decoding: %60$%7Bwindow.API_BASE%7D -> `${window.API_BASE}`
    // It's encoded as %60$%7Bwindow.API_BASE%7D
    content = content.replace(/%60\$\%7Bwindow\.API_BASE\%7D/g, '${window.API_BASE}');

    if (filePath.endsWith('dashboard.html')) {
        content = content.replace(/<script src="\.\.\/js\/admin\.js(.*?)"><\/script>/g, '<script src="../js/admin.js?v=2"></script>');
        content = content.replace(/<script src="\.\.\/js\/helpers\.js(.*?)"><\/script>/g, '<script src="../js/helpers.js?v=2"></script>');
        content = content.replace(/<script src="\.\.\/js\/auth\.js(.*?)"><\/script>/g, '<script src="../js/auth.js?v=2"></script>');
    }
    
    if (content !== original) {
      fs.writeFileSync(filePath, content, 'utf8');
      console.log('Fixed:', filePath);
    }
  }
});
