// Icon Generator Script
// This is a simple SVG-based icon generator for PWA icons
// Run this with: node generate-icons.js

const fs = require('fs');
const path = require('path');

const sizes = [16, 32, 72, 96, 128, 144, 152, 192, 384, 512];
const iconsDir = path.join(__dirname, 'public', 'icons');

// Create icons directory if it doesn't exist
if (!fs.existsSync(iconsDir)) {
    fs.mkdirSync(iconsDir, { recursive: true });
}

function generateSVGIcon(size) {
    return `<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" xmlns="http://www.w3.org/2000/svg">
  <rect width="${size}" height="${size}" fill="#3b82f6" rx="${size * 0.1}"/>
  <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="${size * 0.4}" font-weight="bold" 
        fill="white" text-anchor="middle" dominant-baseline="central">AD</text>
</svg>`;
}

function generateHTMLIcon(size) {
    return `<!DOCTYPE html>
<html>
<head>
    <style>
        body { margin: 0; padding: 0; width: ${size}px; height: ${size}px; }
        .icon { 
            width: ${size}px; 
            height: ${size}px; 
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: ${size * 0.1}px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
            font-size: ${size * 0.4}px;
            font-weight: bold;
            color: white;
        }
    </style>
</head>
<body>
    <div class="icon">AD</div>
</body>
</html>`;
}

// Generate placeholder icons
sizes.forEach(size => {
    // Generate SVG
    const svgContent = generateSVGIcon(size);
    fs.writeFileSync(path.join(iconsDir, `icon-${size}x${size}.svg`), svgContent);
    
    // Generate HTML version for development
    const htmlContent = generateHTMLIcon(size);
    fs.writeFileSync(path.join(iconsDir, `icon-${size}x${size}.html`), htmlContent);
    
    console.log(`Generated icon-${size}x${size}.svg`);
});

// Generate favicon.ico placeholder
const faviconSVG = generateSVGIcon(32);
fs.writeFileSync(path.join(__dirname, 'public', 'favicon.svg'), faviconSVG);

console.log('All placeholder icons generated successfully!');
console.log('To convert SVG to PNG/ICO, use online tools or install imagemagick/sharp');
