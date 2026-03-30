const fs = require('fs');
const path = require('path');

const srcDir = path.resolve(__dirname, '../tests/.playwright-raw-output');
const destDir = path.resolve(__dirname, '../tests/e2e-videos');

if (!fs.existsSync(destDir)) {
  fs.mkdirSync(destDir, { recursive: true });
}

function traverse(dir) {
  const files = fs.readdirSync(dir);
  for (const file of files) {
    const fullPath = path.join(dir, file);
    const stat = fs.statSync(fullPath);
    
    if (stat.isDirectory()) {
      // Each dir is named like "test-name-chromium"
      const testName = file.replace(/-chromium$/, '').substring(0, 60);
      const videoFiles = fs.readdirSync(fullPath).filter(f => f.endsWith('.webm'));
      
      videoFiles.forEach((v, idx) => {
        const suffix = videoFiles.length > 1 ? `-${idx}` : '';
        const newName = `${testName}${suffix}.webm`;
        const destPath = path.join(destDir, newName);
        const videoSrc = path.join(fullPath, v);
        
        fs.copyFileSync(videoSrc, destPath);
        console.log(`✅ Moved: ${newName}`);
      });
      
      traverse(fullPath);
    }
  }
}

if (fs.existsSync(srcDir)) {
  traverse(srcDir);
} else {
  console.log('❌ Source directory not found.');
}
