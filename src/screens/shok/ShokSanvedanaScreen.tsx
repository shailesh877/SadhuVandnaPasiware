import React, { useState, useEffect, useRef } from 'react';
import { View, Text, TextInput, TouchableOpacity, ScrollView, Image, ActivityIndicator, Alert, SafeAreaView, Dimensions, Platform, KeyboardAvoidingView, StyleSheet, StatusBar } from 'react-native';
import { WebView } from 'react-native-webview';
import { Ionicons, MaterialCommunityIcons } from '@expo/vector-icons';
import { Picker } from '@react-native-picker/picker'; // Using existing picker
import * as ImagePicker from 'expo-image-picker';
import * as Sharing from 'expo-sharing';
import * as FileSystem from 'expo-file-system/legacy';
import { Modal } from 'react-native';
import { useLanguage } from '../../context/LanguageContext';
import { obituaryAssets, LOGO } from './obituary_assets';
const { mala: MALA, diya: DIYA, incenseStick: INCENSE } = obituaryAssets;

const { width } = Dimensions.get('window');

const COLORS = {
    primary: '#700000', // Rich Deep Maroon
    secondary: '#C5A028', // Rich Gold
    background: '#FFF9F2', // Warm Ivory
    surface: '#FFFFFF',
    text: '#1A1A1A', // Nearly Black
    textLight: '#52525B', // Darker Zinc Grey
    border: '#E4E4E7',
    accent: '#991B1B', // Red-800
    goldLight: '#F3E5AB',
};

const FormInput = ({ icon, placeholder, value, onChangeText, keyboardType, multiline, numberOfLines, style, onPress, editable = true }: any) => {
    const [isFocused, setIsFocused] = useState(false);
    return (
        <TouchableOpacity 
            activeOpacity={onPress ? 0.7 : 1} 
            onPress={onPress}
            style={[styles.inputWrapper, isFocused && styles.inputFocused, style]}
        >
            <MaterialCommunityIcons name={icon} size={20} color={isFocused ? COLORS.primary : COLORS.textLight} style={styles.inputIcon} />
            <TextInput 
                placeholder={placeholder} 
                placeholderTextColor={COLORS.textLight}
                value={value} 
                onChangeText={onChangeText} 
                keyboardType={keyboardType}
                multiline={multiline}
                numberOfLines={numberOfLines}
                onFocus={() => setIsFocused(true)}
                onBlur={() => setIsFocused(false)}
                editable={editable}
                pointerEvents={onPress ? 'none' : 'auto'}
                style={styles.input}
            />
        </TouchableOpacity>
    );
};

const CustomDatePicker = ({ visible, value, onClose, onConfirm }: any) => {
    // value is "DD-MM-YYYY"
    const [d, m, y] = value ? value.split('-') : ['18', '03', '2026'];
    const [day, setDay] = useState(d);
    const [month, setMonth] = useState(m);
    const [year, setYear] = useState(y);

    const days = Array.from({ length: 31 }, (_, i) => (i + 1).toString().padStart(2, '0'));
    const months = Array.from({ length: 12 }, (_, i) => (i + 1).toString().padStart(2, '0'));
    const years = Array.from({ length: 20 }, (_, i) => (2020 + i).toString());

    return (
        <Modal visible={visible} transparent animationType="slide">
            <View style={styles.modalOverlay}>
                <View style={styles.modalContent}>
                    <View style={styles.modalHeader}>
                        <Text style={styles.modalTitle}>Select Date (तिथि चुनें)</Text>
                        <TouchableOpacity onPress={onClose}>
                            <Ionicons name="close" size={24} color={COLORS.text} />
                        </TouchableOpacity>
                    </View>
                    <View style={styles.pickerRow}>
                        <View style={styles.pickerColWrapper}>
                            <Text style={styles.pickerLabel}>Day</Text>
                            <Picker
                                selectedValue={day}
                                onValueChange={(v) => setDay(v)}
                                style={styles.pickerCol}
                                dropdownIconColor={COLORS.primary}
                            >
                                {days.map(d => <Picker.Item key={d} label={d} value={d} color={COLORS.text} />)}
                            </Picker>
                        </View>
                        <View style={styles.pickerColWrapper}>
                            <Text style={styles.pickerLabel}>Month</Text>
                            <Picker
                                selectedValue={month}
                                onValueChange={(v) => setMonth(v)}
                                style={styles.pickerCol}
                                dropdownIconColor={COLORS.primary}
                            >
                                {months.map(m => <Picker.Item key={m} label={m} value={m} color={COLORS.text} />)}
                            </Picker>
                        </View>
                        <View style={styles.pickerColWrapper}>
                            <Text style={styles.pickerLabel}>Year</Text>
                            <Picker
                                selectedValue={year}
                                onValueChange={(v) => setYear(v)}
                                style={styles.pickerCol}
                                dropdownIconColor={COLORS.primary}
                            >
                                {years.map(y => <Picker.Item key={y} label={y} value={y} color={COLORS.text} />)}
                            </Picker>
                        </View>
                    </View>
                    <TouchableOpacity 
                        onPress={() => onConfirm(`${day}-${month}-${year}`)}
                        style={styles.confirmBtn}
                    >
                        <Text style={styles.confirmBtnText}>Confirm (पुष्टि करें)</Text>
                    </TouchableOpacity>
                </View>
            </View>
        </Modal>
    );
};

const ShokSanvedanaScreen = ({ navigation }: any) => {
    const { t } = useLanguage();
    const [loading, setLoading] = useState(false);
    
    // Form Inputs
    const [name, setName] = useState('');
    const [age, setAge] = useState('');
    const [village, setVillage] = useState('');
    const [district, setDistrict] = useState('');
    const [taluka, setTaluka] = useState('');
    const [samadhiDate, setSamadhiDate] = useState('');
    const [ceremonyTime, setCeremonyTime] = useState('');
    const [familyDetails, setFamilyDetails] = useState('');
    const [language, setLanguage] = useState<'Hindi' | 'English' | 'Gujarati'>('Hindi');
    
    const [photoBase64, setPhotoBase64] = useState<string | null>(null);
    const [isGenerating, setIsGenerating] = useState(false);
    const [showDatePicker, setShowDatePicker] = useState(false);
    const webViewRef = useRef<WebView>(null);

    const handleDateConfirm = (formattedDate: string) => {
        setSamadhiDate(formattedDate);
        setShowDatePicker(false);
    };

    const pickImage = async () => {
        const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ['images'],
            allowsEditing: true,
            aspect: [1, 1],
            quality: 0.8,
            base64: true,
        });

        if (!result.canceled && result.assets[0].base64) {
            setPhotoBase64(`data:image/jpeg;base64,${result.assets[0].base64}`);
        }
    };

    useEffect(() => {
        const data = { name, age, village, district, taluka, samadhiDate, ceremonyTime, familyDetails, language, photo: photoBase64 };
        webViewRef.current?.injectJavaScript(`
            if (window.updateCanvas) {
                window.updateCanvas(${JSON.stringify(data)});
            }
        `);
    }, [name, age, village, district, taluka, samadhiDate, ceremonyTime, familyDetails, language, photoBase64]);

    const handleGenerate = () => {
        if (!name || !photoBase64) {
            Alert.alert(language === 'Hindi' ? "जानकारी अधूरी है" : "Missing Info", 
                        language === 'Hindi' ? "कृपया नाम और फोटो प्रदान करें।" : "Please provide a name and photo.");
            return;
        }
        setIsGenerating(true);
        webViewRef.current?.injectJavaScript(`
            (function() {
                const canvas = document.getElementById("canvas");
                setTimeout(() => {
                    try {
                        const dataUrl = canvas.toDataURL("image/png", 1.0);
                        window.ReactNativeWebView.postMessage(JSON.stringify({ type: 'export', data: dataUrl }));
                    } catch (e) {
                        window.ReactNativeWebView.postMessage(JSON.stringify({ type: 'error', data: e.message }));
                    }
                }, 500);
            })();
        `);
    };

    const onMessage = async (event: any) => {
        try {
            const message = JSON.parse(event.nativeEvent.data);
            if (message.type === 'export') {
                const base64Code = message.data.split("data:image/png;base64,")[1];
                const filename = FileSystem.cacheDirectory + `shok_${Date.now()}.png`;
                await FileSystem.writeAsStringAsync(filename, base64Code, { encoding: FileSystem.EncodingType.Base64 });
                setIsGenerating(false);
                if (await Sharing.isAvailableAsync()) {
                    await Sharing.shareAsync(filename);
                }
            } else if (message.type === 'init_ready') {
                const data = { name, age, village, district, taluka, samadhiDate, ceremonyTime, familyDetails, language, photo: photoBase64 };
                webViewRef.current?.injectJavaScript(`window.updateCanvas(${JSON.stringify(data)})`);
            } else if (message.type === 'error') {
                setIsGenerating(false);
                Alert.alert("Error", message.data);
            }
        } catch (e) {
            setIsGenerating(false);
        }
    };

    const htmlContent = `
    <html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        <link href="https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Hindi\u0026family=Noto+Sans+Gujarati:wght@400;700\u0026display=swap" rel="stylesheet">
        <style>
            body { margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; background: #E5E1D8; overflow: hidden; font-family: 'Tiro Devanagari Hindi', 'Noto Sans Gujarati', serif; }
            canvas { width: 100%; height: 100%; object-fit: contain; background: #E5E1D8; }
        </style>
    </head>
    <body>
        <canvas id="canvas" width="1000" height="1200"></canvas>
        <script>
            window.onerror = function(msg, url, line, col, error) {
                window.ReactNativeWebView.postMessage(JSON.stringify({ type: 'error', data: msg + " at line " + line }));
                return true;
            };
            const canvas = document.getElementById("canvas");
            const ctx = canvas.getContext("2d");

            function drawWrapped(txt, x, y, maxW, fS, align, lang, color = "#333", bold = true) {
                ctx.save(); ctx.textAlign = align; ctx.fillStyle = color;
                const font = lang === "Gujarati" ? "'Noto Sans Gujarati', sans-serif" : "'Tiro Devanagari Hindi', serif";
                ctx.font = (bold ? "bold " : "") + fS + "px " + font;
                let words = txt.split(' '), lines = [], cur = words[0] || '';
                for (let i = 1; i < words.length; i++) {
                    if (ctx.measureText(cur + ' ' + words[i]).width <= maxW) cur += ' ' + words[i];
                    else { lines.push(cur); cur = words[i]; }
                }
                lines.push(cur);
                lines.forEach((l, i) => ctx.fillText(l, x, y + i * fS * 1.3));
                ctx.restore(); return lines.length * fS * 1.3;
            }

            function roundRect(ctx, x, y, width, height, radius) {
                ctx.beginPath();
                ctx.moveTo(x + radius, y);
                ctx.lineTo(x + width - radius, y);
                ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
                ctx.lineTo(x + width, y + height - radius);
                ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
                ctx.lineTo(x + radius, y + height);
                ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
                ctx.lineTo(x, y + radius);
                ctx.quadraticCurveTo(x, y, x + radius, y);
                ctx.closePath();
                ctx.fill();
            }

            window.updateCanvas = function(data) {
                const { name, age, village, district, taluka, samadhiDate, ceremonyTime, familyDetails, language, photo } = data;
                
                const labels = language === "Hindi" ? {
                    tribute: "भावभीनी श्रद्धांजलि",
                    namePrefix: "समाधिस्थ साधुश्री",
                    ageLabel: "आयु",
                    village: "गांव",
                    district: "जिला",
                    taluka: "तालुका",
                    samadhiTitle: "समाधिस्थ तिथि",
                    ceremony: "समाधि विधि",
                    peace: "मैं सर्वशक्तिमान ईश्वर से प्रार्थना करता हूं कि दिवंगत आत्मा को शाश्वत शांति प्रदान करें।",
                    limited: "लि.:"
                } : language === "Gujarati" ? {
                    tribute: "ભાવભીની શ્રદ્ધાંજલિ",
                    namePrefix: "સમાધિસ્થ સાધુશ્રી",
                    ageLabel: "ઉંમર",
                    village: "ગામ",
                    district: "જિલ્લો",
                    taluka: "તાલુકો",
                    samadhiTitle: "સમાધિસ્થ તિથિ",
                    ceremony: "સમાધિવિધિ",
                    peace: "હું સર્વશક્તિમાન ઈશ્વરે પ્રાર્થના કરું છું કે દિવંગત આત્માને શાશ્વત શાંતિ અર્પે.",
                    limited: "લિ.:"
                } : {
                    tribute: "Heartfelt Tribute",
                    namePrefix: "Honorable",
                    ageLabel: "Age",
                    village: "Village",
                    district: "District",
                    taluka: "Taluka",
                    samadhiTitle: "Date",
                    ceremony: "Funeral Date",
                    peace: "I pray to the Almighty God to grant eternal peace to the departed soul.",
                    limited: "Limited:"
                };

                const iLoad = (src) => new Promise(r => {
                    if(!src) return r(null); const i=new Image(); i.onload=()=>r(i); i.onerror=()=>r(null); i.src=src;
                });

                function processImage(img) {
                    if (!img) return null;
                    const c = document.createElement("canvas");
                    c.width = img.width; c.height = img.height;
                    const t = c.getContext("2d");
                    t.drawImage(img, 0, 0);
                    const d = t.getImageData(0, 0, c.width, c.height);
                    const p = d.data;
                    // Ultra-aggressive background removal (White/Off-white/Gray)
                    for (let i = 0; i < p.length; i += 4) {
                        const r=p[i], g=p[i+1], b=p[i+2], sum = r+g+b;
                        const diff = Math.max(r,g,b) - Math.min(r,g,b);
                        // If very bright (sum > 600) OR relatively neutral (diff < 40) and bright
                        if (sum > 610 || (sum > 520 && diff < 40)) p[i+3] = 0; 
                    }
                    t.putImageData(d, 0, 0);
                    
                    // Crop top 15% (remove hanger artifact)
                    const cropC = document.createElement("canvas");
                    const cw = c.width, ch = c.height, cropY = ch * 0.15;
                    cropC.width = cw; cropC.height = ch - cropY;
                    const cropT = cropC.getContext("2d");
                    cropT.drawImage(c, 0, cropY, cw, ch - cropY, 0, 0, cw, ch - cropY);
                    return cropC;
                }

                Promise.all([
                    iLoad(photo),
                    iLoad("${MALA || ''}".length > 10 ? ("${MALA}".startsWith('data:') ? "${MALA}" : "data:image/png;base64,${MALA}") : null),
                    iLoad("${DIYA || ''}".length > 10 ? ("${DIYA}".startsWith('data:') ? "${DIYA}" : "data:image/png;base64,${DIYA}") : null),
                    iLoad("${INCENSE || ''}".length > 10 ? ("${INCENSE}".startsWith('data:') ? "${INCENSE}" : "data:image/png;base64,${INCENSE}") : null),
                    iLoad("${LOGO || ''}".length > 10 ? ("${LOGO}".startsWith('data:') ? "${LOGO}" : "data:image/png;base64,${LOGO}") : null),
                ]).then(([pImg, mImgRaw, dImgRaw, sImgRaw, lImg]) => {
                    const mImg = processImage(mImgRaw);
                    const dImg = processImage(dImgRaw);
                    const sImg = processImage(sImgRaw);
                    ctx.clearRect(0,0,1000,1200);
                    
                    // Background
                    ctx.fillStyle = "#E5E1D8"; ctx.fillRect(0,0,1000,1200);
                    
                    // Logo Watermark (Centered, Semi-transparent)
                    if (lImg) {
                        ctx.save();
                        ctx.globalAlpha = 0.12; 
                        const lw = 400; // Watermark size
                        const lh = (lImg.height / lImg.width) * lw;
                        ctx.drawImage(lImg, 500 - lw/2, 600 - lh/2, lw, lh);
                        ctx.restore();
                    }
                    
                    // Header Area
                    ctx.fillStyle = "black"; ctx.font = "bold 42px 'Tiro Devanagari Hindi'"; ctx.textAlign="center";
                    ctx.fillText(labels.tribute, 500, 54); 

                    // Photo Area (Bigger)
                    const px = 500, py = 295, r = 210;
                    ctx.save(); ctx.beginPath(); ctx.arc(px, py, r, 0, Math.PI*2); ctx.clip();
                    ctx.fillStyle = "#ddd"; ctx.fillRect(px-r, py-r, r*2, r*2);
                    if (pImg) {
                        const s = Math.max((r*2)/pImg.width, (r*2)/pImg.height);
                        ctx.drawImage(pImg, px - (pImg.width*s)/2, py - (pImg.height*s)/2, pImg.width*s, pImg.height*s);
                    }
                    ctx.restore();
                    
                    // Garland & Lamps (Refined positioning & transparency)
                    if (mImg) {
                        // Wide V-shape (900px) fully spanning the circle + margin
                        // py-120 start (hanger cropped). Ends at 675. Name at 730. 
                        ctx.drawImage(mImg, px - 450, py - 120, 900, 500); 
                    }
                    if (dImg) {
                        // Lamps with removed backgrounds
                        ctx.drawImage(dImg, 80, 200, 140, 420); // Side Left
                        ctx.drawImage(dImg, 780, 200, 140, 420); // Side Right
                    }
                    // Incense stick removed per user request

                    // Name Section (Shifted down slightly for wider garland)
                    ctx.fillStyle = "black"; ctx.font = "bold 52px 'Tiro Devanagari Hindi'";
                    ctx.fillText(labels.namePrefix + " " + (name || "..."), 500, 730); // 720 -> 730

                    // Deceased Date
                    ctx.font = "bold 32px sans-serif";
                    ctx.fillText(labels.samadhiTitle + ": " + (samadhiDate || "--"), 500, 780); // 840 -> 780

                    // Prayer Message Section (Shifted up)
                    ctx.strokeStyle = "rgba(0,0,0,0.6)"; ctx.lineWidth = 1; 
                    ctx.strokeRect(180, 820, 640, 180); // 880 -> 820
                    
                    ctx.fillStyle = "black";
                    if (language === "Hindi") {
                        ctx.font = "bold 38px 'Tiro Devanagari Hindi'";
                        ctx.fillText("मैं सर्वशक्तिमान ईश्वर से प्रार्थना", 500, 870);
                        ctx.fillText("करता हूं कि दिवंगत आत्मा", 500, 920);
                        ctx.fillText("को शाश्वत शांति प्रदान करें।", 500, 970);
                    } else {
                        drawWrapped(labels.peace, 500, 860, 600, 36, "center", language, "black", true);
                    }

                    // Footer / Funeral Info (Inside Black Bar)
                    ctx.fillStyle = "black";
                    ctx.fillRect(180, 1005, 640, 50); // Right below prayer box (box ends at 1000)
                    
                    ctx.fillStyle = "white";
                    ctx.textAlign = "center";
                    ctx.font = "bold 28px 'Tiro Devanagari Hindi'";
                    ctx.fillText(labels.ceremony + ": " + (ceremonyTime || "--"), 500, 1040);

                    // Layout Divider
                    ctx.strokeStyle = "black"; ctx.lineWidth = 3;
                    ctx.beginPath(); ctx.moveTo(500, 1070); ctx.lineTo(500, 1180); ctx.stroke(); 

                    // Bottom Left: Family details
                    ctx.font = "bold 26px sans-serif"; ctx.textAlign="left";
                    ctx.fillStyle = "black";
                    ctx.fillText(labels.limited, 60, 1085); 
                    drawWrapped(familyDetails || "", 60, 1120, 420, 24, "left", language, "black", false);

                    // Bottom Right: Address fields
                    ctx.textAlign="left";
                    const ax = 550;
                    ctx.fillText(labels.village + ":", ax, 1085);
                    ctx.fillText(village || "-", ax + 200, 1085);
                    
                    ctx.fillText(labels.district + ":", ax, 1125);
                    ctx.fillText(district || "-", ax + 200, 1125);
                    
                    ctx.fillText(labels.taluka + ":", ax, 1165); // Original line 409
                    ctx.fillText(taluka || "-", ax + 200, 1165);
                });
            };
            window.ReactNativeWebView.postMessage(JSON.stringify({ type: 'init_ready' }));
        </script>
    </body>
    </html>
    `;

    const RenderSectionHeader = ({ title, icon }: { title: string, icon: any }) => (
        <View style={styles.sectionHeader}>
            <View style={styles.sectionIconBg}>
                <MaterialCommunityIcons name={icon} size={18} color={COLORS.surface} />
            </View>
            <Text style={styles.sectionTitle}>{title}</Text>
        </View>
    );

    return (
        <View style={styles.container}>
            <StatusBar barStyle="dark-content" backgroundColor={COLORS.surface} />
            <SafeAreaView style={{ flex: 1 }}>
                <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
                    <View style={styles.header}>
                        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.iconButton}>
                            <Ionicons name="chevron-back" size={24} color={COLORS.text} />
                        </TouchableOpacity>
                        <Text style={styles.headerTitle}>Shraddhanjali Designer</Text>
                        <TouchableOpacity 
                            onPress={handleGenerate} 
                            disabled={isGenerating}
                            style={[styles.doneButton, isGenerating && styles.disabledButton]}
                        >
                            {isGenerating ? <ActivityIndicator size="small" color="#fff" /> : (
                                <>
                                    <Ionicons name="share-social" size={18} color="white" />
                                    <Text style={styles.doneText}>Share</Text>
                                </>
                            )}
                        </TouchableOpacity>
                    </View>

                    <ScrollView style={{ flex: 1 }} showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: 60 }}>
                        <View style={styles.topPreviewWrapper}>
                            <View style={styles.previewContainer}>
                                <View style={styles.previewCard}>
                                    <View style={styles.previewFrame}>
                                        <WebView
                                            ref={webViewRef}
                                            originWhitelist={['*']}
                                            source={{ html: htmlContent }}
                                            style={styles.webView}
                                            scrollEnabled={false}
                                            onMessage={onMessage}
                                            javaScriptEnabled={true}
                                        />
                                    </View>
                                </View>
                            </View>
                            <View style={styles.previewBadge}>
                                <Text style={styles.previewBadgeText}>LIVE PREVIEW</Text>
                            </View>
                        </View>

                        <View style={styles.formContainer}>
                            <View style={styles.langToggleWrapper}>
                                <Text style={styles.langLabel}>Template Language:</Text>
                                <View style={styles.langToggle}>
                                    <TouchableOpacity 
                                        onPress={() => setLanguage('Hindi')} 
                                        style={[styles.langBtn, language === 'Hindi' && styles.langBtnActive]}
                                    >
                                        <Text style={[styles.langText, language === 'Hindi' && styles.langTextActive]}>हिन्दी</Text>
                                    </TouchableOpacity>
                                    <TouchableOpacity 
                                        onPress={() => setLanguage('Gujarati')} 
                                        style={[styles.langBtn, language === 'Gujarati' && styles.langBtnActive]}
                                    >
                                        <Text style={[styles.langText, language === 'Gujarati' && styles.langTextActive]}>ગુજરાતી</Text>
                                    </TouchableOpacity>
                                    <TouchableOpacity 
                                        onPress={() => setLanguage('English')} 
                                        style={[styles.langBtn, language === 'English' && styles.langBtnActive]}
                                    >
                                        <Text style={[styles.langText, language === 'English' && styles.langTextActive]}>EN</Text>
                                    </TouchableOpacity>
                                </View>
                            </View>

                            <RenderSectionHeader title="Person's Identity" icon="account-badge" />
                            <View style={styles.card}>
                                <TouchableOpacity onPress={pickImage} style={styles.imagePicker}>
                                    {photoBase64 ? (
                                        <Image source={{ uri: photoBase64 }} style={styles.selectedImage} />
                                    ) : (
                                        <View style={styles.imagePickerPlaceholder}>
                                            <View style={styles.imageIconCircle}>
                                                <Ionicons name="image" size={32} color={COLORS.primary} />
                                            </View>
                                            <Text style={styles.imagePickerText}>Select Portrait Photo</Text>
                                            <Text style={styles.imagePickerSubtext}>High quality JPG/PNG recommended</Text>
                                        </View>
                                    )}
                                </TouchableOpacity>

                                <FormInput 
                                    icon="account" 
                                    placeholder="Full Name (पूरा नाम)" 
                                    value={name} 
                                    onChangeText={setName} 
                                />

                                <FormInput 
                                    icon="calendar" 
                                    placeholder="Age" 
                                    value={age} 
                                    onChangeText={setAge} 
                                    keyboardType="numeric" 
                                />
                            </View>

                            <RenderSectionHeader title="Location Details" icon="map-marker-path" />
                            <View style={styles.card}>
                                <FormInput 
                                    icon="map-marker" 
                                    placeholder="Village (गाँव)" 
                                    value={village} 
                                    onChangeText={setVillage} 
                                />
                                <View style={styles.row}>
                                    <FormInput 
                                        style={{ flex: 1, marginRight: 10 }}
                                        icon="map-marker-radius" 
                                        placeholder="District (जिला)" 
                                        value={district} 
                                        onChangeText={setDistrict} 
                                    />
                                    <FormInput 
                                        style={{ flex: 1 }}
                                        icon="map-marker-distance" 
                                        placeholder="Taluka (तालुका)" 
                                        value={taluka} 
                                        onChangeText={setTaluka} 
                                    />
                                </View>
                            </View>

                            <RenderSectionHeader title="Ceremony Details" icon="calendar-check" />
                            <View style={styles.card}>
                                <FormInput 
                                    icon="calendar-clock" 
                                    placeholder="Samadhi Date (समाधि तिथि)" 
                                    value={samadhiDate} 
                                    onChangeText={setSamadhiDate} 
                                    onPress={() => setShowDatePicker(true)}
                                    editable={false}
                                />
                                <CustomDatePicker
                                    visible={showDatePicker}
                                    value={samadhiDate}
                                    onClose={() => setShowDatePicker(false)}
                                    onConfirm={handleDateConfirm}
                                />
                                <FormInput 
                                    icon="clock-outline" 
                                    placeholder="Funeral Date (अंतिम संस्कार तिथि)" 
                                    value={ceremonyTime} 
                                    onChangeText={setCeremonyTime} 
                                />
                            </View>

                            <RenderSectionHeader title="Bereaved Family (शोकाकुल)" icon="account-group" />
                            <View style={styles.card}>
                                <FormInput 
                                    icon="text-box-outline" 
                                    placeholder="Family Members Names..." 
                                    value={familyDetails} 
                                    onChangeText={setFamilyDetails} 
                                    multiline 
                                    numberOfLines={4} 
                                    style={{ height: 120, alignItems: 'flex-start' }}
                                />
                            </View>
                        </View>
                    </ScrollView>
                </KeyboardAvoidingView>
            </SafeAreaView>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: COLORS.background,
    },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: 16,
        paddingTop: Platform.OS === 'android' ? (StatusBar.currentHeight || 20) + 12 : 12,
        paddingBottom: 12,
        backgroundColor: COLORS.surface,
        borderBottomWidth: 1,
        borderBottomColor: COLORS.border,
        justifyContent: 'space-between',
    },
    headerTitle: {
        flex: 1,
        fontSize: 19,
        fontWeight: 'bold',
        color: COLORS.primary,
        textAlign: 'center',
    },
    iconButton: {
        width: 44,
        height: 44,
        borderRadius: 22,
        backgroundColor: '#F8F8F8',
        justifyContent: 'center',
        alignItems: 'center',
    },
    doneButton: {
        backgroundColor: COLORS.primary,
        paddingHorizontal: 18,
        paddingVertical: 10,
        borderRadius: 25,
        flexDirection: 'row',
        alignItems: 'center',
        shadowColor: COLORS.primary,
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.2,
        shadowRadius: 10,
        elevation: 6,
    },
    disabledButton: {
        backgroundColor: '#CCC',
        elevation: 0,
    },
    doneText: {
        color: 'white',
        fontWeight: 'bold',
        fontSize: 15,
        marginLeft: 8,
    },
    topPreviewWrapper: {
        backgroundColor: COLORS.surface,
        paddingBottom: 25,
        borderBottomLeftRadius: 30,
        borderBottomRightRadius: 30,
        elevation: 10,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 10 },
        shadowOpacity: 0.05,
        shadowRadius: 10,
    },
    previewContainer: {
        paddingTop: 15,
        alignItems: 'center',
    },
    previewCard: {
        width: width * 0.9,
        aspectRatio: 1000/1200,
        borderRadius: 20,
        backgroundColor: 'white',
        shadowColor: 'black',
        shadowOffset: { width: 0, height: 15 },
        shadowOpacity: 0.2,
        shadowRadius: 25,
        elevation: 20,
        padding: 5,
    },
    previewFrame: {
        flex: 1,
        borderRadius: 15,
        overflow: 'hidden',
        backgroundColor: '#F8F8F8',
    },
    previewBadge: {
        position: 'absolute',
        bottom: 10,
        alignSelf: 'center',
        backgroundColor: COLORS.secondary,
        paddingHorizontal: 12,
        paddingVertical: 4,
        borderRadius: 10,
    },
    previewBadgeText: {
        color: 'white',
        fontSize: 10,
        fontWeight: '900',
        letterSpacing: 1.2,
    },
    webView: {
        flex: 1,
    },
    formContainer: {
        padding: 20,
    },
    langToggleWrapper: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        marginBottom: 25,
        backgroundColor: '#FFF',
        padding: 12,
        borderRadius: 15,
        borderWidth: 1,
        borderColor: '#F0F0F0',
    },
    langLabel: {
        fontSize: 14,
        fontWeight: '600',
        color: COLORS.text,
    },
    langToggle: {
        flexDirection: 'row',
        backgroundColor: '#F0F0F0',
        borderRadius: 10,
        padding: 3,
        width: 220,
    },
    langBtn: {
        flex: 1,
        paddingVertical: 8,
        borderRadius: 8,
        alignItems: 'center',
    },
    langBtnActive: {
        backgroundColor: 'white',
        elevation: 2,
        shadowColor: '#000',
        shadowOpacity: 0.1,
    },
    langText: {
        fontWeight: 'bold',
        color: COLORS.textLight,
        fontSize: 13,
    },
    langTextActive: {
        color: COLORS.primary,
    },
    sectionHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        marginBottom: 15,
        marginTop: 5,
    },
    sectionIconBg: {
        width: 32,
        height: 32,
        borderRadius: 10,
        backgroundColor: COLORS.primary,
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 10,
    },
    sectionTitle: {
        fontSize: 16,
        fontWeight: '800',
        color: COLORS.text,
    },
    card: {
        backgroundColor: COLORS.surface,
        borderRadius: 20,
        padding: 16,
        marginBottom: 30,
        borderWidth: 1,
        borderColor: '#EEF2F6',
        shadowColor: '#000',
        shadowOpacity: 0.02,
        shadowRadius: 15,
    },
    imagePicker: {
        width: '100%',
        height: 200,
        borderRadius: 15,
        backgroundColor: '#FAFAFA',
        borderWidth: 2,
        borderStyle: 'dashed',
        borderColor: '#DDD',
        marginBottom: 20,
        overflow: 'hidden',
        justifyContent: 'center',
        alignItems: 'center',
    },
    imagePickerPlaceholder: {
        alignItems: 'center',
    },
    imagePickerText: {
        marginTop: 10,
        fontSize: 15,
        fontWeight: 'bold',
        color: COLORS.text,
    },
    imagePickerSubtext: {
        fontSize: 12,
        color: COLORS.textLight,
        marginTop: 2,
    },
    imageIconCircle: {
        width: 54,
        height: 54,
        borderRadius: 27,
        backgroundColor: '#FFF2F2',
        justifyContent: 'center',
        alignItems: 'center',
    },
    selectedImage: {
        width: '100%',
        height: '100%',
    },
    inputWrapper: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#F7F8F9',
        borderRadius: 12,
        borderWidth: 1.5,
        borderColor: '#F0F1F2',
        marginBottom: 15,
        paddingHorizontal: 15,
    },
    inputFocused: {
        borderColor: COLORS.secondary,
        backgroundColor: '#FFF',
    },
    inputIcon: {
        marginRight: 10,
    },
    input: {
        flex: 1,
        paddingVertical: 14,
        fontSize: 16,
        fontWeight: '600',
        color: COLORS.text,
    },
    row: {
        flexDirection: 'row',
    },
    modalOverlay: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.5)',
        justifyContent: 'flex-end',
    },
    modalContent: {
        backgroundColor: 'white',
        borderTopLeftRadius: 30,
        borderTopRightRadius: 30,
        padding: 24,
    },
    modalHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 20,
    },
    modalTitle: {
        fontSize: 18,
        fontWeight: 'bold',
        color: COLORS.text,
    },
    pickerRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        marginBottom: 24,
    },
    pickerColWrapper: {
        flex: 1,
        alignItems: 'center',
    },
    pickerCol: {
        width: '100%',
        height: 200,
        color: COLORS.text,
    },
    pickerLabel: {
        fontSize: 12,
        fontWeight: 'bold',
        color: COLORS.textLight,
        marginBottom: 5,
    },
    confirmBtn: {
        backgroundColor: COLORS.primary,
        paddingVertical: 15,
        borderRadius: 15,
        alignItems: 'center',
    },
    confirmBtnText: {
        color: 'white',
        fontWeight: 'bold',
        fontSize: 16,
    }
});

export default ShokSanvedanaScreen;
