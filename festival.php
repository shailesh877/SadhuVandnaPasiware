<?php
include("header.php");

if(!isset($_SESSION['sadhu_user_id'])){
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['sadhu_user_id'];
$user_q = $con->query("SELECT * FROM tbl_members WHERE mobile='$user_id' LIMIT 1");
$user = $user_q->fetch_assoc();

$initial_name = $user['name'] ?? '';
$initial_mobile = $user['phone'] ?? ''; 
$initial_address = ($user['city'] ?? '') . ', ' . ($user['state'] ?? '');
$initial_photo = $user['profile_photo'] ? 'uploads/photo/' . $user['profile_photo'] : '';
$frames = [];
$frame_q = $con->query("SELECT * FROM tbl_festival_frames ORDER BY id DESC");
while($r = $frame_q->fetch_assoc()){
    $frames[] = $r;
}
?>

<title>Festival Card - Sadhu Vandana</title>
<!-- Google Fonts: Playfair Display -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

<!-- Main Content -->
<main class="flex-1 px-4 md:px-10 py-20 md:ml-20 mb-13 md:mb-0 max-w-7xl mx-auto w-full">
    
    <div class="grid lg:grid-cols-2 gap-8 items-start">
        
        <!-- TOOLBOX -->
        <div class="bg-white p-8 rounded-3xl shadow-xl border border-orange-100">
            <h1 class="text-3xl font-extrabold text-orange-600 mb-6 flex items-center gap-3">
                <i class="fa-solid fa-wand-magic-sparkles"></i> 
                Festival Card Generator
            </h1>
            
            <div class="space-y-4">
                <!-- Frame Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Design</label>
                    <div class="grid grid-cols-3 gap-3 max-h-60 overflow-y-auto p-2 border border-gray-100 rounded-xl bg-gray-50">
                        <!-- Default Option -->
                        <div onclick="selectFrame('')" class="cursor-pointer border-4 border-orange-500 rounded-xl overflow-hidden relative aspect-square frame-option shadow-sm transition" id="frame_default">
                           <div class="w-full h-full bg-gradient-to-tr from-orange-400 to-orange-600 flex items-center justify-center text-white text-sm font-bold p-2 text-center">
                                <i class="fa-solid fa-paintbrush mr-1"></i> Default
                           </div>
                        </div>
                        <!-- Dynamic Frames -->
                        <?php foreach($frames as $f): ?>
                        <div onclick="selectFrame('uploads/festival_frames/<?= $f['frame_image'] ?>', this)" 
                             class="cursor-pointer border-4 border-transparent hover:border-orange-300 rounded-xl overflow-hidden relative aspect-square frame-option shadow-sm bg-white transition group">
                            <img src="uploads/festival_frames/<?= htmlspecialchars($f['frame_image']) ?>" class="w-full h-full object-cover transition group-hover:scale-105">
                            <div class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-[10px] p-1 truncate text-center backdrop-blur-sm">
                                <?= htmlspecialchars($f['title']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Layout Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Text Layout</label>
                    <div class="grid grid-cols-3 gap-3">
                        <!-- Style 1: Right Photo, Left Blocks -->
                        <div onclick="selectLayout(1, this)" class="layout-option cursor-pointer border-4 border-orange-500 rounded-xl bg-slate-100 aspect-video relative overflow-hidden shadow-sm transition hover:scale-105" id="layout_1_btn">
                            <div class="absolute bottom-2 left-1 w-2/3 h-3 bg-red-700 rounded-r-lg"></div>
                            <div class="absolute bottom-2 right-2 w-5 h-5 bg-gray-400 rounded-full border-2 border-white"></div>
                        </div>

                        <!-- Style 2: Left Photo, Right Strip -->
                        <div onclick="selectLayout(2, this)" class="layout-option cursor-pointer border-4 border-transparent rounded-xl bg-slate-100 aspect-video relative overflow-hidden shadow-sm transition hover:scale-105">
                            <div class="absolute bottom-3 left-2 w-5 h-5 bg-gray-400 rounded-full border-2 border-white z-10"></div>
                            <div class="absolute bottom-3 left-4 right-0 h-4 bg-blue-900 rounded-l-lg"></div>
                        </div>

                        <!-- Style 3: Floating Center Card -->
                        <div onclick="selectLayout(3, this)" class="layout-option cursor-pointer border-4 border-transparent rounded-xl bg-slate-100 aspect-video relative overflow-hidden shadow-sm transition hover:scale-105">
                             <div class="absolute bottom-1 left-4 right-4 h-6 bg-white shadow-md rounded-lg mx-auto border-t-2 border-orange-200"></div>
                             <div class="absolute bottom-4 left-0 right-0 mx-auto w-6 h-6 bg-gray-400 rounded-full border-2 border-white"></div>
                        </div>

                        <!-- Style 4: Full Wave Bottom -->
                        <div onclick="selectLayout(4, this)" class="layout-option cursor-pointer border-4 border-transparent rounded-xl bg-slate-100 aspect-video relative overflow-hidden shadow-sm transition hover:scale-105">
                             <div class="absolute bottom-0 w-full h-4 bg-red-600 rounded-tl-lg"></div>
                             <div class="absolute bottom-2 right-4 w-6 h-6 bg-gray-400 rounded-full border-2 border-white z-10"></div>
                        </div>

                        <!-- Style 5: Capsule & Circle -->
                        <div onclick="selectLayout(5, this)" class="layout-option cursor-pointer border-4 border-transparent rounded-xl bg-slate-100 aspect-video relative overflow-hidden shadow-sm transition hover:scale-105">
                             <div class="absolute bottom-2 left-2 w-1/2 h-5 bg-gradient-to-r from-red-800 to-red-500 rounded-full"></div>
                             <div class="absolute bottom-2 right-2 w-6 h-6 bg-gray-400 rounded-full border-2 border-white"></div>
                        </div>

                        <!-- Style 6: Corner Accent -->
                        <div onclick="selectLayout(6, this)" class="layout-option cursor-pointer border-4 border-transparent rounded-xl bg-slate-100 aspect-video relative overflow-hidden shadow-sm transition hover:scale-105">
                             <div class="absolute bottom-0 left-0 w-full h-4 bg-orange-500"></div>
                             <div class="absolute bottom-0 left-0 w-1/3 h-4 bg-orange-700"></div>
                             <div class="absolute bottom-2 right-3 w-6 h-6 bg-gray-400 rounded-full border-2 border-white z-10"></div>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Festival Name</label>
                    <input id="festival" type="text" placeholder="e.g. Happy Akshaya Tritiya" 
                           class="w-full border-2 border-orange-50 p-4 rounded-2xl focus:border-orange-400 outline-none transition-all shadow-sm"
                           value="">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                    <input id="name" type="text" placeholder="Enter your name" 
                           class="w-full border-2 border-orange-50 p-4 rounded-2xl focus:border-orange-400 outline-none transition-all shadow-sm"
                           value="<?= htmlspecialchars($initial_name) ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                    <input id="mobile" type="text" placeholder="Enter mobile number" 
                           class="w-full border-2 border-orange-50 p-4 rounded-2xl focus:border-orange-400 outline-none transition-all shadow-sm"
                           value="<?= htmlspecialchars($initial_mobile) ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input id="address" type="text" placeholder="Enter address" 
                           class="w-full border-2 border-orange-50 p-4 rounded-2xl focus:border-orange-400 outline-none transition-all shadow-sm"
                           value="<?= htmlspecialchars($initial_address) ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Upload Photo (Optional)</label>
                    <input type="file" id="photo" accept="image/*"
                           class="w-full border-2 border-orange-50 p-4 rounded-2xl focus:border-orange-400 outline-none transition-all shadow-sm bg-gray-50">
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4">
                    <button onclick="generateCard()" 
                            class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-4 rounded-2xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-arrows-rotate"></i> Update
                    </button>
                    <button onclick="downloadCard()" 
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-2xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-download"></i> Download
                    </button>
                </div>
            </div>
        </div>

        <!-- PREVIEW -->
        <div class="flex flex-col items-center sticky top-24">
            <div class="relative p-2 bg-gradient-to-tr from-orange-400 to-orange-600 rounded-[2rem] shadow-2xl overflow-hidden mb-4">
                <canvas id="canvas" width="1080" height="1080" 
                        class="w-full max-w-[500px] block rounded-[1.8rem] bg-white"></canvas>
            </div>
            <p class="text-gray-500 text-sm italic">
                <i class="fa-solid fa-circle-info mr-1"></i> Preview shows 1080x1080 high-res card
            </p>
        </div>

    </div>
</main>

<script>
const canvas = document.getElementById("canvas");
const ctx = canvas.getContext("2d");

// Predefined colors from user image
const COLORS = {
    bg: "#1a4658",
    gold: "#d4af37",
    accent: "#ffcc00",
    text_white: "#ffffff",
    bar_dark: "#222222",
    bar_red: "#cc0000"
};

// Initial draw
window.onload = () => {
    generateCard();
};

// Global Frame Variable
let selectedFrameUrl = '';
let selectedLayout = 1;

function selectLayout(id, el) {
    selectedLayout = id;
    
    // UI Update
    document.querySelectorAll('.layout-option').forEach(div => {
        div.classList.remove('border-orange-500');
        div.classList.add('border-transparent');
    });
    
    if(el) {
        el.classList.remove('border-transparent');
        el.classList.add('border-orange-500');
    }

    generateCard();
}

function selectFrame(url, el) {
    selectedFrameUrl = url;
    
    // UI Update - Reset all borders
    document.querySelectorAll('.frame-option').forEach(div => {
        if(div.id === 'frame_default'){ 
             div.classList.remove('border-orange-500'); 
             div.classList.add('border-transparent');
        } else {
             div.classList.remove('border-orange-500'); 
             div.classList.add('border-transparent');
        }
    });

    // Set active border
    if(el) {
        el.classList.remove('border-transparent');
        el.classList.add('border-orange-500');
    } else {
        const def = document.getElementById('frame_default');
        if(def) {
            def.classList.remove('border-transparent');
            def.classList.add('border-orange-500');
        }
    }
    
    generateCard();
}

function generateCard() {
    const festival = document.getElementById("festival").value;
    const name = document.getElementById("name").value;
    const mobile = document.getElementById("mobile").value;
    const address = document.getElementById("address").value;
    const photoInput = document.getElementById("photo").files[0];

    // Clear Canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (selectedFrameUrl) {
        // Draw Selected Frame
        const bgImg = new Image();
        bgImg.crossOrigin = "Anonymous";
        bgImg.src = selectedFrameUrl;
        bgImg.onload = () => {
             ctx.drawImage(bgImg, 0, 0, canvas.width, canvas.height);
             
             // Draw user details on top of frame
             drawOverlays(festival, name, mobile, address, photoInput);
        };
    } else {
        // Draw Default "Manual" Design
        
        // 1. Background
        ctx.fillStyle = COLORS.bg;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    
        // 2. Texture/Subtle Gradient
        const grad = ctx.createRadialGradient(540, 540, 100, 540, 540, 800);
        grad.addColorStop(0, "rgba(255,255,255,0.05)");
        grad.addColorStop(1, "rgba(0,0,0,0.1)");
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    
        // 3. Draw Hanging Torans (Mala)
        drawTorans();
    
        // 4. Draw Festive Arch (The Frame)
        drawFestiveArch();
    
        // 5. Drawing Decorative Elements
        drawDecorations();
        
        // 5.5 Draw Logo
        drawLogo(); 
    
        // Overlays
        drawOverlays(festival, name, mobile, address, photoInput);
    }
}

function drawOverlays(festival, name, mobile, address, photoInput) {
    // Draw Overlays based on selected layout
    if (photoInput) {
        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                drawLayout(selectedLayout, festival, name, mobile, address, img);
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(photoInput);
    } else {
        const defaultPhotoUrl = '<?= $initial_photo ?>';
        if (defaultPhotoUrl) {
            const img = new Image();
            img.onload = function() {
                drawLayout(selectedLayout, festival, name, mobile, address, img);
            };
            img.src = defaultPhotoUrl;
        } else {
            drawLayout(selectedLayout, festival, name, mobile, address, null);
        }
    }
}

// Helper to wrap text and return the new Y position
// Helper to get wrapped lines (used by drawSmartName internally, but good to have)
function getLines(ctx, text, maxWidth) {
    var words = text.split(" ");
    var lines = [];
    var currentLine = words[0];

    for (var i = 1; i < words.length; i++) {
        var word = words[i];
        var width = ctx.measureText(currentLine + " " + word).width;
        if (width < maxWidth) {
            currentLine += " " + word;
        } else {
            lines.push(currentLine);
            currentLine = word;
        }
    }
    lines.push(currentLine);
    return lines;
}

// Draw name with auto-scaling to avoid overflow
function drawSmartName(ctx, name, x, y, maxWidth, maxLines, initialFontSize, align) {
    let fontSize = initialFontSize;
    ctx.font = "bold " + fontSize + "px sans-serif";
    
    let lines = getLines(ctx, name, maxWidth);
    
    // Reduce font until it fits in maxLines
    while (lines.length > maxLines && fontSize > 20) {
        fontSize -= 2;
        ctx.font = "bold " + fontSize + "px sans-serif";
        lines = getLines(ctx, name, maxWidth);
    }
    
    // Draw the lines
    for (let i = 0; i < lines.length; i++) {
        ctx.fillText(lines[i], x, y);
        y += fontSize + 5; // Line Height spacing
    }
    
    return y; // Return next Y position
}

function drawLayout(id, festival, name, mobile, address, photoImg) {
    
    // Draw Logo on top right for all layouts by default
    if(selectedFrameUrl || id) drawLogo();

    // Render Festival Name (Common)
    ctx.fillStyle = COLORS.text_white;
    ctx.textAlign = "center";
    ctx.shadowColor = "rgba(0,0,0,0.5)";
    ctx.shadowBlur = 10;
    
    // Determine Text Y based on layout
    let textY = 350;
    
    ctx.font = "bold 80px 'Playfair Display', serif";
    if (festival.includes(" ")) {
        const parts = festival.split(" ");
        ctx.fillText(parts[0], 540, 300);
        ctx.font = "italic bold 100px serif";
        ctx.fillText(parts.slice(1).join(" "), 540, 420);
    } else {
        ctx.fillText(festival, 540, 350);
    }
    
    ctx.shadowBlur = 0; // Reset shadow

    // ----- DRAW SPECIFIC LAYOUTS -----
    
    if (id === 1) {
        // STYLE 1: The Classic (Red Bars Left, Photo Right)
        
        // Red Bar - Increased Height for 2 lines
        ctx.fillStyle = COLORS.bar_red;
        roundRect(ctx, 40, 850, 650, 160, 20, true, false);
        
        // Dark Bar
        ctx.fillStyle = COLORS.bar_dark;
        roundRect(ctx, 100, 810, 400, 50, 25, true, false);
        ctx.fillStyle = COLORS.gold;
        ctx.font = "bold 25px sans-serif";
        ctx.textAlign = "center";
        ctx.fillText("SADHUVANDNA SAMAJ", 300, 843);

        // Text
        ctx.textAlign = "left";
        ctx.fillStyle = "white";
        // removed fixed font set here, handled by drawSmartName
        
        // Name: Start higher (900), MaxWidth 600, Max 2 lines, Font 55
        let nextY = drawSmartName(ctx, name, 80, 915, 600, 2, 55, 'left'); 
        
        ctx.font = "bold 45px sans-serif";
        ctx.fillStyle = COLORS.accent;
        ctx.fillText("📞 " + mobile, 80, nextY + 10); 
        
        ctx.fillStyle = "white";
        ctx.font = "500 28px sans-serif";
        
        // Address
        let dAddr = address.length > 50 ? address.substring(0, 47) + "..." : address;
        ctx.fillText("📍 " + dAddr, 80, nextY + 45);

        // Photo (Right)
        drawUserPhoto(photoImg, 820, 850, 160);
    }
    else if (id === 2) {
        // STYLE 2: Left Photo, Blue Ribbon Right
        
        // Ribbon - Increased Height
        const ribbonY = 880;
        const ribbonH = 160;
        
        ctx.fillStyle = "#0f172a"; 
        ctx.beginPath();
        ctx.moveTo(250, ribbonY);
        ctx.lineTo(1080, ribbonY);
        ctx.lineTo(1080, ribbonY + ribbonH);
        ctx.lineTo(250, ribbonY + ribbonH);
        ctx.arc(250, ribbonY + ribbonH/2, ribbonH/2, 90, 270); 
        ctx.fill();

        ctx.fillStyle = COLORS.accent;
        ctx.fillRect(300, ribbonY + ribbonH - 10, 780, 10);

        // Text
        ctx.textAlign = "left";
        ctx.fillStyle = "white";
        
        // Name: Start higher, Max 2 lines
        let nextY = drawSmartName(ctx, name, 380, ribbonY + 60, 650, 2, 55, 'left');
        
        ctx.font = "bold 32px sans-serif";
        ctx.fillStyle = "#ccc";
        let displayContact = mobile + " | " + address;
        if(displayContact.length > 55) displayContact = displayContact.substring(0, 52) + "...";
        
        ctx.fillText(displayContact, 380, nextY + 15);

        // Photo (Left)
        drawUserPhoto(photoImg, 200, 900, 150, "#0f172a");
    }
    else if (id === 3) {
        // STYLE 3: Floating Card Center
        
        // Card BG
        ctx.fillStyle = "rgba(27, 213, 238, 0.95)";
        ctx.shadowColor = "rgba(0,0,0,0.3)";
        ctx.shadowBlur = 20;
        roundRect(ctx, 100, 850, 880, 180, 30, true, false);
        ctx.shadowBlur = 0;

        ctx.fillStyle = COLORS.bar_red;
        roundRect(ctx, 300, 850, 480, 10, {tl:10, tr:10, bl:0, br:0}, true, false);

        ctx.textAlign = "center";
        
        // Photo
        drawUserPhoto(photoImg, 540, 820, 130, "skyblue");
        
        ctx.fillStyle = "#333";
        // Center alignment handled by context
        let nextY = drawSmartName(ctx, name, 540, 950, 800, 2, 55, 'center');
        
        ctx.font = "500 32px sans-serif";
        ctx.fillStyle = COLORS.bar_red;
        ctx.fillText(mobile + "  •  " + address, 540, nextY + 10);
    }
    else if (id === 4) {
        // STYLE 4: Wave Bottom
        
        ctx.fillStyle = "#b91c1c"; // Red
        ctx.beginPath();
        ctx.moveTo(0, 850);
        ctx.bezierCurveTo(300, 950, 700, 800, 1080, 900);
        ctx.lineTo(1080, 1080);
        ctx.lineTo(0, 1080);
        ctx.fill();

        ctx.fillStyle = "#7f1d1d"; // Darker Red
        ctx.beginPath();
        ctx.moveTo(0, 920);
        ctx.bezierCurveTo(300, 1000, 600, 950, 1080, 1000);
        ctx.lineTo(1080, 1080);
        ctx.lineTo(0, 1080);
        ctx.fill();

        // Text
        ctx.textAlign = "left";
        ctx.fillStyle = "white";
        
        // Wrap Name
        let nextY = drawSmartName(ctx, name, 50, 930, 600, 2, 60, 'left');

        ctx.font = "500 28px sans-serif";
        ctx.fillStyle = "#fecdd3";
        let dAddr = address.length > 50 ? address.substring(0, 47) + "..." : address;
        ctx.fillText("📍 " + dAddr, 50, nextY + 15);
        
        ctx.textAlign = "right";
        ctx.font = "bold 40px sans-serif";
        ctx.fillStyle = "#fbbf24";
        // Align phone with address
        ctx.fillText("📞 " + mobile, 1030, nextY + 15);

        // Photo (Right Floating)
        drawUserPhoto(photoImg, 900, 800, 140, "white");
    }
    else if (id === 5) {
        // STYLE 5: Capsule (Pill) Left
        const capW = 700;
        const capH = 160;
        const capX = 50;
        const capY = 880;
        
        const grad = ctx.createLinearGradient(capX, 0, capX+capW, 0);
        grad.addColorStop(0, "#881337");
        grad.addColorStop(1, "#be123c");
        ctx.fillStyle = grad;
        
        roundRect(ctx, capX, capY, capW, capH, 80, true, false);
        
        ctx.strokeStyle = "rgba(255,255,255,0.3)";
        ctx.lineWidth = 4;
        roundRect(ctx, capX+10, capY+10, capW-20, capH-20, 70, false, true);

        // Text
        ctx.textAlign = "left";
        ctx.fillStyle = "white";
        
        // Wrap Name inside capsule
        let nextY = drawSmartName(ctx, name, capX + 60, capY + 70, 580, 2, 55, 'left');
        
        ctx.font = "400 30px sans-serif";
        ctx.fillStyle = "#fecdd3";
        ctx.fillText(mobile + "  |  " + address, capX + 60, nextY + 15);

        // Photo (Right)
        drawUserPhoto(photoImg, 900, 900, 160, "white");
    }
    else {
        // STYLE 6: Full Strip Blue/Orange (Detailed)
        
        const barH = 180;
        const barY = 900;
        
        ctx.fillStyle = "#1e3a8a"; // Blue
        ctx.fillRect(0, barY, 1080, barH);
        
        ctx.fillStyle = "#f97316";
        ctx.fillRect(0, barY, 400, 15);
        ctx.fillStyle = "#ea580c";
        ctx.fillRect(400, barY, 680, 15);

        // Info Block
        ctx.fillStyle = "white";
        ctx.textAlign = "right";
        ctx.font = "bold 55px sans-serif"; // Reduced
        
        
        let nextY = drawSmartName(ctx, name, 1000, barY + 65, 500, 2, 55, 'right');
        
        ctx.font = "400 30px sans-serif";
        ctx.fillStyle = "#93c5fd";
        
        let fullAddr = mobile + "  •  " + address;
        if(fullAddr.length > 55) fullAddr = fullAddr.substring(0, 52) + "...";
        
        ctx.fillText(fullAddr, 1030, nextY + 15);

        // Photo (Left Corner, overlapping)
        drawUserPhoto(photoImg, 160, 880, 150, "white");
    }
}

/**
 * Draws text within a specified width and number of lines, scaling down font size if necessary.
 * Returns the Y coordinate for the next line of text.
 * @param {CanvasRenderingContext2D} ctx - The 2D rendering context.
 * @param {string} text - The text to draw.
 * @param {number} x - The x-coordinate of the text.
 * @param {number} y - The y-coordinate of the text (baseline of the first line).
 * @param {number} maxWidth - The maximum width the text can occupy.
 * @param {number} maxLines - The maximum number of lines allowed.
 * @param {number} initialFontSize - The initial font size to attempt.
 * @param {string} textAlign - The text alignment ('left', 'center', 'right').
 * @returns {number} The y-coordinate for the next line of text after the drawn block.
 */
function drawSmartName(ctx, text, x, y, maxWidth, maxLines, initialFontSize, textAlign) {
    ctx.save();
    ctx.textAlign = textAlign;
    ctx.fillStyle = "white"; // Default color for name
    let currentY = y;
    let fontSize = initialFontSize;
    const minFontSize = 20; // Minimum font size to scale down to
    const lineHeightMultiplier = 1.2; // Line height as a multiple of font size

    let words = text.split(' ');
    let lines = [];
    let currentLine = words[0] || '';

    // Function to measure text width
    const measureText = (txt, size) => {
        ctx.font = `bold ${size}px sans-serif`;
        return ctx.measureText(txt).width;
    };

    // Try to fit text by scaling down font size
    while (fontSize >= minFontSize) {
        lines = [];
        currentLine = words[0] || '';
        
        for (let i = 1; i < words.length; i++) {
            let word = words[i];
            let testLine = currentLine + ' ' + word;
            if (measureText(testLine, fontSize) <= maxWidth) {
                currentLine = testLine;
            } else {
                lines.push(currentLine);
                currentLine = word;
            }
        }
        lines.push(currentLine);

        if (lines.length <= maxLines) {
            break; // Text fits within maxLines at this fontSize
        }
        fontSize -= 2; // Reduce font size and try again
    }

    // If still too many lines, truncate and add ellipsis
    if (lines.length > maxLines) {
        lines = lines.slice(0, maxLines);
        let lastLine = lines[maxLines - 1];
        while (measureText(lastLine + '...', fontSize) > maxWidth && lastLine.length > 0) {
            lastLine = lastLine.substring(0, lastLine.length - 1);
        }
        lines[maxLines - 1] = lastLine + '...';
    }

    // Draw the lines
    ctx.font = `bold ${fontSize}px sans-serif`;
    for (let i = 0; i < lines.length; i++) {
        ctx.fillText(lines[i], x, currentY);
        currentY += fontSize * lineHeightMultiplier;
    }
    
    ctx.restore();
    return currentY - (fontSize * lineHeightMultiplier) + (fontSize * 0.8); // Return next Y, adjusted for baseline
}

function drawTorans() {
    const spacing = 120;
    const startX = 100;
    ctx.strokeStyle = COLORS.gold;
    ctx.lineWidth = 4;

    for (let i = 0; i < 8; i++) {
        let x = startX + i * spacing;
        let y = 0;
        let h = 180 + Math.sin(i * 1.5) * 50;
        
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x, h);
        ctx.stroke();

        // Little circles on string
        for(let j=0; j<5; j++) {
            ctx.beginPath();
            ctx.arc(x, h * (j/5), 5, 0, Math.PI*2);
            ctx.fillStyle = COLORS.accent;
            ctx.fill();
        }

        // Pendant
        ctx.beginPath();
        ctx.arc(x, h, 15, 0, Math.PI*2);
        ctx.fillStyle = COLORS.gold;
        ctx.fill();
        ctx.stroke();
    }
}

function drawFestiveArch() {
    ctx.save();
    ctx.translate(540, 500);
    
    // Outer Arch
    ctx.beginPath();
    ctx.moveTo(-350, 300);
    ctx.quadraticCurveTo(-350, -200, 0, -350);
    ctx.quadraticCurveTo(350, -200, 350, 300);
    ctx.strokeStyle = COLORS.gold;
    ctx.lineWidth = 20;
    ctx.stroke();

    // Inner Arch Fill
    ctx.beginPath();
    ctx.moveTo(-330, 300);
    ctx.quadraticCurveTo(-330, -180, 0, -330);
    ctx.quadraticCurveTo(330, -180, 330, 300);
    ctx.fillStyle = "rgba(0,100,50,0.3)";
    ctx.fill();
    
    ctx.restore();
}

function drawDecorations() {
    // simplified drawing of pots/flowers
    ctx.fillStyle = "#e67e22"; // Clay Color
    
    // Left Pot
    ctx.beginPath();
    ctx.arc(350, 650, 80, 0, Math.PI*2);
    ctx.fill();
    
    // Right Pot
    ctx.beginPath();
    ctx.arc(730, 650, 80, 0, Math.PI*2);
    ctx.fill();
    
    // Center Pot
    ctx.beginPath();
    ctx.arc(540, 680, 100, 0, Math.PI*2);
    ctx.fill();
}

function drawLogo() {
    const logoImg = new Image();
    logoImg.src = "images/logo.png";
    logoImg.onload = () => {
        ctx.fillStyle = "white";
        ctx.beginPath();
        ctx.arc(970, 70, 50, 0, Math.PI*2);
        ctx.fill();
        ctx.drawImage(logoImg, 930, 30, 80, 80);
    };
}

// drawBottomBars Removed (replaced by drawLayout logic)

function drawUserPhoto(img, px, py, pr, borderColor) {
    // Default values if not passed
    px = px || 820;
    py = py || 850;
    pr = pr || 160;
    borderColor = borderColor || "white";

    // Glow
    const glow = ctx.createRadialGradient(px, py, pr - 20, px, py, pr + 40);
    glow.addColorStop(0, "rgba(255,255,0,0.4)");
    glow.addColorStop(1, "rgba(255,255,0,0)");
    ctx.fillStyle = glow;
    ctx.beginPath();
    ctx.arc(px, py, pr + 20, 0, Math.PI*2);
    ctx.fill();

    // Circle Frame
    ctx.save();
    ctx.beginPath();
    ctx.arc(px, py, pr, 0, Math.PI*2);
    ctx.clip();
    
    // Background for photo
    ctx.fillStyle = "#ddd";
    ctx.fillRect(px-pr, py-pr, pr*2, pr*2);

    if (img) {
        // center crop draw
        // Calculate aspect ratio
        const scale = Math.max((pr*2) / img.width, (pr*2) / img.height);
        const nw = img.width * scale;
        const nh = img.height * scale;
        
        // Center the image
        const nx = px - nw/2;
        const ny = py - nh/2;
        
        ctx.drawImage(img, nx, ny, nw, nh);
    } else {
        // Draw placeholder avatar
        ctx.fillStyle = "#666";
        ctx.beginPath();
        ctx.arc(px, py + 50, pr*0.6, 0, Math.PI*2);
        ctx.fill();
        ctx.beginPath();
        ctx.arc(px, py - 30, pr*0.3, 0, Math.PI*2);
        ctx.fill();
    }
    
    ctx.restore();
    
    // Border
    ctx.strokeStyle = borderColor;
    ctx.lineWidth = 15;
    ctx.beginPath();
    ctx.arc(px, py, pr, 0, Math.PI*2);
    ctx.stroke();
}

function downloadCard() {
    const link = document.createElement('a');
    const timestamp = new Date().getTime(); // timestamp ensures unique name
    link.download = `Sadhu-Vandana-Card-${timestamp}.png`;
    link.href = canvas.toDataURL("image/png");
    link.click();
}

// Utility for rounded rectangles
function roundRect(ctx, x, y, width, height, radius, fill, stroke) {
  if (typeof radius === 'undefined') radius = 5;
  if (typeof radius === 'number') {
    radius = {tl: radius, tr: radius, br: radius, bl: radius};
  } else {
    var defaultRadius = {tl: 0, tr: 0, br: 0, bl: 0};
    for (var side in defaultRadius) {
      radius[side] = radius[side] || defaultRadius[side];
    }
  }
  ctx.beginPath();
  ctx.moveTo(x + radius.tl, y);
  ctx.lineTo(x + width - radius.tr, y);
  ctx.quadraticCurveTo(x + width, y, x + width, y + radius.tr);
  ctx.lineTo(x + width, y + height - radius.br);
  ctx.quadraticCurveTo(x + width, y + height, x + width - radius.br, y + height);
  ctx.lineTo(x + radius.bl, y + height);
  ctx.quadraticCurveTo(x, y + height, x, y + height - radius.bl);
  ctx.lineTo(x, y + radius.tl);
  ctx.quadraticCurveTo(x, y, x + radius.tl, y);
  ctx.closePath();
  if (fill) ctx.fill();
  if (stroke) ctx.stroke();
}
</script>

</body>
</html>
