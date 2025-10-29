#!/usr/bin/env node

/**
 * Generate Favicon Files
 * Creates various sizes of favicon from SVG source
 */

const fs = require('fs');
const path = require('path');

// Modern SVG favicon with CO-Z branding
const faviconSVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
  <!-- Background with gradient -->
  <defs>
    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#2f4686;stop-opacity:1" />
      <stop offset="100%" style="stop-color:#3956a3;stop-opacity:1" />
    </linearGradient>
  </defs>
  <rect width="48" height="48" rx="10" fill="url(#bgGrad)"/>
  
  <!-- CO-Z Text -->
  <g fill="white">
    <!-- C -->
    <path d="M10 16 Q10 12, 14 12 L18 12 Q20 12, 20 14 L20 16 L17 16 L17 14.5 Q17 14, 16 14 L14 14 Q13 14, 13 15 L13 21 Q13 22, 14 22 L16 22 Q17 22, 17 21.5 L17 20 L20 20 L20 22 Q20 24, 18 24 L14 24 Q10 24, 10 20 Z"/>
    
    <!-- O -->
    <path d="M10 28 Q10 24, 14 24 L16 24 Q20 24, 20 28 L20 32 Q20 36, 16 36 L14 36 Q10 36, 10 32 Z M13 28 L13 32 Q13 33, 14 33 L16 33 Q17 33, 17 32 L17 28 Q17 27, 16 27 L14 27 Q13 27, 13 28 Z"/>
    
    <!-- Dash -->
    <rect x="22" y="29" width="6" height="3" rx="1.5" fill="#9fb4ff"/>
    
    <!-- Z -->
    <path d="M30 24 L38 24 L38 27 L33 33 L38 33 L38 36 L30 36 L30 33 L35 27 L30 27 Z"/>
  </g>
  
  <!-- Accent -->
  <circle cx="40" cy="10" r="2.5" fill="#9fb4ff" opacity="0.8"/>
</svg>`;

// Write the SVG file
const svgPath = path.join(__dirname, 'public', 'icons', 'favicon.svg');
fs.writeFileSync(svgPath, faviconSVG);
console.log('✓ Created favicon.svg');

// Create a simple HTML file for manual conversion reference
const htmlTemplate = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Favicon Preview - CO-Z Co-Workspace</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .preview-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .preview-item img {
            image-rendering: crisp-edges;
        }
        h1 { color: #2f4686; }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2196F3;
            margin: 20px 0;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <h1>🎨 CO-Z Favicon Preview</h1>
    
    <div class="info">
        <strong>📝 Note:</strong> The SVG favicon has been generated at <code>public/icons/favicon.svg</code>
        <br><br>
        <strong>To generate PNG/ICO files:</strong>
        <ol>
            <li>Use an online tool like <a href="https://realfavicongenerator.net/" target="_blank">RealFaviconGenerator</a></li>
            <li>Upload <code>public/icons/favicon.svg</code></li>
            <li>Download the generated files</li>
            <li>Replace files in <code>public/</code> and <code>public/icons/</code></li>
        </ol>
    </div>

    <div class="preview-grid">
        <div class="preview-item">
            <img src="/icons/favicon.svg" width="16" height="16" alt="16x16">
            <p>16×16 (Browser Tab)</p>
        </div>
        <div class="preview-item">
            <img src="/icons/favicon.svg" width="32" height="32" alt="32x32">
            <p>32×32 (Taskbar)</p>
        </div>
        <div class="preview-item">
            <img src="/icons/favicon.svg" width="48" height="48" alt="48x48">
            <p>48×48 (Windows Site)</p>
        </div>
        <div class="preview-item">
            <img src="/icons/favicon.svg" width="96" height="96" alt="96x96">
            <p>96×96 (Desktop)</p>
        </div>
        <div class="preview-item">
            <img src="/icons/favicon.svg" width="192" height="192" alt="192x192">
            <p>192×192 (Android)</p>
        </div>
    </div>

    <h2>Current Favicon</h2>
    <div style="background: white; padding: 20px; border-radius: 8px; display: inline-block;">
        <img src="/icons/favicon.svg" width="128" height="128" alt="favicon">
    </div>
</body>
</html>`;

const htmlPath = path.join(__dirname, 'public', 'favicon-preview.html');
fs.writeFileSync(htmlPath, htmlTemplate);
console.log('✓ Created favicon-preview.html');

console.log('\n✅ Favicon files generated!');
console.log('\n📋 Next steps:');
console.log('1. Visit http://localhost:8000/favicon-preview.html to preview');
console.log('2. Use https://realfavicongenerator.net/ to generate all sizes');
console.log('3. Or use the SVG directly (modern browsers support SVG favicons)');
