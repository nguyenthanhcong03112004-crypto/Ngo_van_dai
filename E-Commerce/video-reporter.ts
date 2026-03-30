import type { Reporter, TestCase, TestResult } from '@playwright/test/reporter';
import * as fs from 'fs';
import * as path from 'path';

export default class VideoRenameReporter implements Reporter {
  private videoOutputDir: string;

  constructor() {
    this.videoOutputDir = path.join(process.cwd(), '../tests/e2e-videos');
    if (!fs.existsSync(this.videoOutputDir)) {
      fs.mkdirSync(this.videoOutputDir, { recursive: true });
    }
    console.log(`\n📂 VideoRenameReporter initialized. Output: ${this.videoOutputDir}`);
  }

  onTestEnd(test: TestCase, result: TestResult): void {
    console.log(`\n🔎 Test ended: "${test.title}" [${result.status}]`);
    
    const videoAttachment = result.attachments.find(a => a.name === 'video');
    if (!videoAttachment) {
      console.log(`   ⚠️ No video attachment found.`);
      return;
    }

    if (!videoAttachment.path) {
      console.log(`   ⚠️ Video attachment found but no path available.`);
      return;
    }

    const originalPath = videoAttachment.path;
    console.log(`   🎥 Video located at: ${originalPath}`);

    const slug = test.title
      .toLowerCase()
      .replace(/[^a-z0-9\u00C0-\u024F\s]/g, '')
      .trim()
      .replace(/\s+/g, '-')
      .substring(0, 60);

    const status  = result.status === 'passed' ? 'pass' : 'fail';
    const newName = `${slug}-${status}.webm`;
    const newPath = path.join(this.videoOutputDir, newName);

    try {
      if (fs.existsSync(originalPath)) {
        fs.copyFileSync(originalPath, newPath);
        // We copy instead of rename just in case Playwright is still holding the file
        console.log(`   ✅ Video saved: ${newName}`);
      } else {
        console.log(`   ❌ Original video file does not exist on disk: ${originalPath}`);
      }
    } catch (err: any) {
      console.log(`   ❌ Error saving video: ${err.message}`);
    }
  }
}
