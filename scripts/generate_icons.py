from PIL import Image
import os

source_path = 'assets/original_logo.png'
dest_dir = 'assets/'

def generate_icons():
    if not os.path.exists(source_path):
        print(f"Error: {source_path} not found.")
        return

    img = Image.open(source_path).convert("RGBA")
    
    # 1. Main Icon (1024x1024)
    # Resize keeping aspect ratio, then paste on transparent canvas if needed, 
    # OR just resize if it's already square-ish.
    # User's logo is round. Let's make it 1024x1024.
    
    icon_size = (1024, 1024)
    icon_img = img.resize(icon_size, Image.Resampling.LANCZOS)
    icon_img.save(os.path.join(dest_dir, 'icon.png'))
    icon_img.save(os.path.join(dest_dir, 'logo.png'))
    print("Generated icon.png and logo.png")

    # 2. Adaptive Icon (1024x1024)
    # Needs safe area. Logo should be approx 60-70% of size centered.
    # Background white.
    adaptive_bg = Image.new('RGBA', (1024, 1024), (255, 255, 255, 255))
    target_size = 700 # ~68%
    rate = target_size / max(img.size)
    new_size = (int(img.size[0] * rate), int(img.size[1] * rate))
    
    resized_logo = img.resize(new_size, Image.Resampling.LANCZOS)
    
    # Calculate center position
    x = (1024 - new_size[0]) // 2
    y = (1024 - new_size[1]) // 2
    
    adaptive_bg.paste(resized_logo, (x, y), resized_logo)
    adaptive_bg.save(os.path.join(dest_dir, 'adaptive-icon.png'))
    print("Generated adaptive-icon.png")

    # 3. Splash Icon
    # Usually strictly the logo.
    # Let's use the original aspect ratio resized to match width 512 approx?
    # Or just use the original icon logic.
    # Expo default splash-icon.png is often smaller or specific.
    # Let's save a 512x512 version.
    
    splash_bg = Image.new('RGBA', (1024, 1024), (0,0,0,0)) # Transparent
    # Reuse previous resized logic if suitable, but let's make it bigger.
    # Just save the icon.png copy as splash-icon.png is often fine if it's transparent.
    # But let's verify if user wants specific dimensions. standard is just icon.
    icon_img.save(os.path.join(dest_dir, 'splash-icon.png'))
    print("Generated splash-icon.png")

if __name__ == "__main__":
    generate_icons()
