import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, ScrollView, Alert, ActivityIndicator, Image, StyleSheet, Modal, Pressable, Platform } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import api from '../../services/api';

const CATEGORY_MAP = [
    { name: 'ताज़ा खबर', icon: '⚡' },
    { name: 'राष्ट्रीय', icon: '🇮🇳' },
    { name: 'राज्य', icon: '🏛️' },
    { name: 'जिला', icon: '📍' },
    { name: 'राजनीति', icon: '🗳️' },
    { name: 'धर्म', icon: '🛕' },
    { name: 'शिक्षा', icon: '🎓' },
    { name: 'खेल', icon: '🏆' },
    { name: 'व्यापार', icon: '💼' },
    { name: 'कृषि', icon: '🌾' },
    { name: 'मनोरंजन', icon: '🎬' },
    { name: 'सामाजिक', icon: '🤝' },
    { name: 'दुर्घटना', icon: '⚠️' },
    { name: 'मृत्यु/श्रद्धांजलि', icon: '🕯️' },
    { name: 'विज्ञापन', icon: '📢' }
];

const CreateNewsScreen = ({ navigation }: any) => {
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [category, setCategory] = useState('ताज़ा खबर');
    const [mediaFiles, setMediaFiles] = useState<{ uri: string; type: 'image' | 'video' }[]>([]);
    const [loading, setLoading] = useState(false);

    // AI Writer States
    const [aiModalVisible, setAiModalVisible] = useState(false);
    const [aiKeywords, setAiKeywords] = useState('');
    const [generating, setGenerating] = useState(false);

    // Terms and Policies States
    const [termsAccepted, setTermsAccepted] = useState(false);
    const [policyAccepted, setPolicyAccepted] = useState(false);
    
    const [termsModalVisible, setTermsModalVisible] = useState(false);
    const [policyModalVisible, setPolicyModalVisible] = useState(false);

    const [termsScrolledToBottom, setTermsScrolledToBottom] = useState(false);
    const [policyScrolledToBottom, setPolicyScrolledToBottom] = useState(false);

    const pickMedia = async () => {
        try {
            // Request permission first
            const permissionResult = await ImagePicker.requestMediaLibraryPermissionsAsync();
            if (!permissionResult.granted) {
                Alert.alert("Permission Required", "Please allow access to your files to upload news media.");
                return;
            }

            const result = await ImagePicker.launchImageLibraryAsync({
                mediaTypes: ImagePicker.MediaTypeOptions.All,
                allowsMultipleSelection: true,
                quality: 0.8,
            });

            if (!result.canceled && result.assets) {
                const selectedMedia = result.assets.map(asset => ({
                    uri: asset.uri,
                    type: asset.type === 'video' ? 'video' : 'image'
                }));
                setMediaFiles(prev => [...prev, ...selectedMedia]);
            }
        } catch (err) {
            console.error(err);
            Alert.alert("Error", "Failed to select media files.");
        }
    };

    const removeMedia = (index: number) => {
        setMediaFiles(prev => prev.filter((_, i) => i !== index));
    };

    const generateAiNews = (keywords: string, cat: string) => {
        const cleanKw = keywords.trim();
        if (!cleanKw) {
            return {
                title: "ताज़ा अपडेट: " + cat,
                description: `आज की विशेष खबर: ${cat} से संबंधित महत्वपूर्ण जानकारी आ रही है। विस्तृत रिपोर्ट का इंतज़ार है।`
            };
        }

        switch (cat) {
            case 'ताज़ा खबर':
                return {
                    title: `बड़ी खबर: ${cleanKw} को लेकर आया नया अपडेट, लोगों में बढ़ी उत्सुकता`,
                    description: `ताज़ा जानकारी के अनुसार, ${cleanKw} को लेकर एक बड़ी घोषणा हुई है। सूत्रों से मिली खबर के मुताबिक इस फैसले से क्षेत्र में काफी हलचल है। प्रशासन इस मामले पर पूरी निगरानी बनाए हुए है और जल्द ही विस्तृत ब्यौरा साझा किया जाएगा।`
                };
            case 'राष्ट्रीय':
                return {
                    title: `देशहित: ${cleanKw} को लेकर केंद्र सरकार का बड़ा फैसला, राष्ट्रव्यापी प्रभाव की संभावना`,
                    description: `देश भर में ${cleanKw} को लेकर चर्चाएं तेज हो गई हैं। केंद्र सरकार द्वारा जारी आधिकारिक बयान में इस कदम को ऐतिहासिक बताया गया है। सुरक्षा और विकास के दृष्टिकोण से यह फैसला बेहद महत्वपूर्ण माना जा रहा है।`
                };
            case 'राज्य':
                return {
                    title: `राज्य समाचार: ${cleanKw} पर मुख्यमंत्री ने बुलाई उच्च-स्तरीय बैठक, दिए सख्त निर्देश`,
                    description: `राज्य सरकार ने ${cleanKw} की वर्तमान स्थिति को देखते हुए नए दिशा-निर्देश जारी किए हैं। अधिकारियों को काम में तेजी लाने के निर्देश दिए गए हैं ताकि जनता को समय पर राहत मिल सके। विपक्षी दलों ने भी इस मुद्दे पर प्रतिक्रिया दी है।`
                };
            case 'जिला':
                return {
                    title: `जिला अपडेट: ${cleanKw} के संबंध में जिलाधिकारी ने जारी किया नया आदेश`,
                    description: `हमारे स्थानीय प्रतिनिधि के अनुसार, जिला मुख्यालय में ${cleanKw} को लेकर प्रशासन मुस्तैद है। आम लोगों की समस्याओं को ध्यान में रखते हुए विशेष हेल्पलाइन और टीमों का गठन किया गया है। अधिकारियों ने नागरिकों से सहयोग की अपील की है।`
                };
            case 'राजनीति':
                return {
                    title: `राजनीतिक हलचल: ${cleanKw} पर छिड़ा सियासी घमासान, नेताओं के बीच जुबानी जंग तेज`,
                    description: `विधानसभा चुनावों से पहले ${cleanKw} का मुद्दा गरमा गया है। विभिन्न राजनीतिक दलों के प्रवक्ताओं ने एक-दूसरे पर गंभीर आरोप लगाए हैं। इस बहस के बाद जनता का रुख क्या होगा, यह आने वाला समय ही बताएगा।`
                };
            case 'धर्म':
                return {
                    title: `धार्मिक आयोजन: ${cleanKw} को लेकर श्रद्धालुओं में भारी उत्साह, भव्य तैयारियां शुरू`,
                    description: `आगामी त्योहार के अवसर पर ${cleanKw} से जुड़े धार्मिक स्थलों को सजाया जा रहा है। मंदिर ट्रस्ट और स्थानीय सेवा समितियों ने सुरक्षा और दर्शन के लिए विशेष प्रबंध किए हैं। संतों ने इस पावन अवसर पर शांति और सौहार्द का संदेश दिया है।`
                };
            case 'शिक्षा':
                return {
                    title: `शिक्षा जगत: ${cleanKw} परीक्षा परिणाम और नई गाइडलाइंस घोषित`,
                    description: `विभाग द्वारा जारी प्रेस नोट के अनुसार ${cleanKw} से जुड़े नियमों में बड़े बदलाव किए गए हैं। छात्र-छात्राओं के हित को ध्यान में रखते हुए इस बार नई शिक्षण पद्धति को लागू किया जा रहा है। शिक्षकों ने इस फैसले का स्वागत किया है।`
                };
            case 'खेल':
                return {
                    title: `खेल समाचार: ${cleanKw} प्रतियोगिता में रोमांचक मुकाबला, खिलाड़ियों ने दिखाया दम`,
                    description: `आज आयोजित हुए फाइनल मैच में ${cleanKw} को लेकर जबरदस्त उत्साह देखने को मिला। दर्शकों की भारी भीड़ के बीच खिलाड़ियों ने उत्कृष्ट प्रदर्शन कर नया रिकॉर्ड बनाया। विजेता टीम को मुख्य अतिथि द्वारा पुरस्कृत किया गया।`
                };
            case 'व्यापार':
                return {
                    title: `कारोबार: ${cleanKw} में भारी उछाल, बाजार में व्यापारियों के चेहरे खिले`,
                    description: `आर्थिक विश्लेषकों का मानना है कि ${cleanKw} के चलते आने वाले दिनों में बाजार की स्थिति और सुधरेगी। नए निवेश और ग्राहकों की मांग बढ़ने से खुदरा और थोक व्यापारियों को अच्छा मुनाफा होने की उम्मीद है।`
                };
            case 'कृषि':
                return {
                    title: `कृषि चौपाल: ${cleanKw} को लेकर वैज्ञानिकों ने जारी की नई सलाह, किसानों के लिए वरदान`,
                    description: `उन्नत खेती और पैदावार बढ़ाने के लिए ${cleanKw} पर कृषि विभाग ने जागरूकता शिविर का आयोजन किया। वैज्ञानिकों ने फसलों की सुरक्षा और जैविक खादों के उपयोग पर किसानों के सवालों के लाइव जवाब दिए।`
                };
            case 'मनोरंजन':
                return {
                    title: `मनोरंजन: ${cleanKw} का धमाकेदार टीज़र रिलीज़, फैन्स ने सोशल मीडिया पर मचाया तहलका`,
                    description: `दर्शकों की उत्सुकता को बढ़ाते हुए मेकर्स ने ${cleanKw} की पहली झलक पेश कर दी है। टीज़र में बेहतरीन एक्शन और डायलॉग्स की भरमार है। फैंस सिनेमाघरों में इसके आने का बेसब्री से इंतज़ार कर रहे हैं।`
                };
            case 'सामाजिक':
                return {
                    title: `सामाजिक सरोकार: ${cleanKw} अभियान में जुटीं स्वयंसेवी संस्थाएं, लोगों की मदद की गुहार`,
                    description: `समाज कल्याण के अंतर्गत ${cleanKw} को बढ़ावा देने के लिए एक विशाल सभा का आयोजन किया गया। वक्ताओं ने समाज के सभी वर्गों से इसमें आगे आकर हिस्सा लेने का आग्रह किया ताकि ज़रूरतमंदों तक मदद पहुंचाई जा सके।`
                };
            case 'दुर्घटना':
                return {
                    title: `सड़क हादसा: ${cleanKw} के पास भीषण टक्कर, राहत और बचाव कार्य जारी`,
                    description: `आज सुबह ${cleanKw} के समीप एक वाहन अनियंत्रित होकर पलट गया। हादसे में घायलों को तुरंत पास के अस्पताल में भर्ती कराया गया है। पुलिस ने मामला दर्ज कर जांच शुरू कर दी है और यातायात बहाल कर दिया गया है।`
                };
            case 'मृत्यु/श्रद्धांजलि':
                return {
                    title: `श्रद्धांजलि: ${cleanKw} का निधन, क्षेत्र में शोक की लहर`,
                    description: `अत्यंत दुख के साथ सूचित किया जाता है कि ${cleanKw} अब हमारे बीच नहीं रहे। उनके निधन पर सामाजिक और राजनीतिक संगठनों के प्रतिनिधियों ने गहरा शोक व्यक्त किया है। ईश्वर उनकी आत्मा को शांति प्रदान करे।`
                };
            case 'विज्ञापन':
                return {
                    title: `विशेष सेल: ${cleanKw} पर पाएं धमाकेदार छूट, सीमित समय के लिए ऑफर`,
                    description: `ग्राहकों के लिए सुनहरा मौका! ${cleanKw} की खरीद पर अब आपको मिल रहा है बंपर डिस्काउंट और शानदार उपहार। आज ही अपने नजदीकी स्टोर पर पधारें या ऑनलाइन ऑर्डर करें। स्टॉक सीमित है!`
                };
            default:
                return {
                    title: `विशेष रिपोर्ट: ${cleanKw} को लेकर नई जानकारियां सामने आईं`,
                    description: `${cleanKw} के बारे में ताजा अपडेट सामने आया है। इस घटनाक्रम से जुड़ी अन्य महत्वपूर्ण बातें जल्द ही मुख्य समाचार बुलेटिन में दिखाई जाएंगी।`
                };
        }
    };

    const handleAiGenerate = () => {
        if (!aiKeywords.trim()) {
            Alert.alert("Keywords Required", "Please enter a few keywords first.");
            return;
        }
        setGenerating(true);
        setTimeout(() => {
            const draft = generateAiNews(aiKeywords, category);
            setGenerating(false);
            setTitle(draft.title);
            setDescription(draft.description);
            setAiModalVisible(false);
            setAiKeywords('');
            Alert.alert("Draft Applied", "AI news title and description populated successfully!");
        }, 1500);
    };

    const handleScroll = (event: any, setScrolledState: (val: boolean) => void) => {
        const { layoutMeasurement, contentOffset, contentSize } = event.nativeEvent;
        // Verify user reaches the bottom (within 25 pixels boundary margin)
        const isCloseToBottom = layoutMeasurement.height + contentOffset.y >= contentSize.height - 25;
        if (isCloseToBottom) {
            setScrolledState(true);
        }
    };

    const handleSubmit = async () => {
        if (!title.trim() || !description.trim()) {
            Alert.alert("Missing Fields", "Please enter a news title and description.");
            return;
        }

        if (!termsAccepted || !policyAccepted) {
            Alert.alert("Consent Required", "Please read and accept both the Disclaimer/Terms and the Privacy Policy to submit news.");
            return;
        }

        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('title', title);
            formData.append('description', description);
            formData.append('category', category);

            mediaFiles.forEach((file, index) => {
                const filename = file.uri.split('/').pop() || `news_media_${index}.${file.type === 'video' ? 'mp4' : 'jpg'}`;
                const match = /\.(\w+)$/.exec(filename);
                let type = '';
                if (file.type === 'video') {
                    type = match ? `video/${match[1]}` : `video/mp4`;
                } else {
                    type = match ? `image/${match[1]}` : `image/jpeg`;
                }
                formData.append('media[]', {
                    uri: file.uri,
                    name: filename,
                    type
                } as any);
            });

            const res = await api.post('/create_news.php', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            if (res.data.status === 'success') {
                Alert.alert("Success", "News posted successfully!", [
                    { text: "OK", onPress: () => navigation.goBack() }
                ]);
            } else {
                Alert.alert("Error", res.data.message || "Failed to post news.");
            }
        } catch (error) {
            console.error(error);
            Alert.alert("Error", "Network request failed. Please try again.");
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView style={{ flex: 1, backgroundColor: 'white' }} edges={['top']}>
            {/* Header */}
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Ionicons name="arrow-back" size={24} color="black" />
                </TouchableOpacity>
                <Text style={styles.headerTitle}>Add New News</Text>
                <View style={{ width: 40 }} />
            </View>

            <ScrollView contentContainerStyle={styles.scrollContainer} keyboardShouldPersistTaps="handled">
                <Text style={styles.sectionTitle}>News Media (Photos & Videos)</Text>

                {/* Dotted upload container */}
                <TouchableOpacity 
                    onPress={pickMedia} 
                    style={[
                        styles.uploadPlaceholder, 
                        mediaFiles.length > 0 ? styles.uploadPlaceholderWithImages : {}
                    ]}
                    activeOpacity={0.8}
                >
                    {mediaFiles.length === 0 ? (
                        <View style={styles.uploadPlaceholderInner}>
                            <Ionicons name="cloud-upload-outline" size={44} color="#9ca3af" />
                            <Text style={styles.uploadText}>Tap to select news images or videos</Text>
                        </View>
                    ) : (
                        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.previewsScroll}>
                            {mediaFiles.map((file, index) => (
                                <View key={index} style={styles.imageItemContainer}>
                                    <Image source={{ uri: file.uri }} style={styles.imagePreview} />
                                    {file.type === 'video' && (
                                        <View style={{
                                            position: 'absolute',
                                            top: 0,
                                            left: 0,
                                            right: 0,
                                            bottom: 0,
                                            backgroundColor: 'rgba(0,0,0,0.35)',
                                            justifyContent: 'center',
                                            alignItems: 'center'
                                        }}>
                                            <Ionicons name="play-circle" size={32} color="white" />
                                        </View>
                                    )}
                                    <TouchableOpacity style={styles.removeImageBtn} onPress={() => removeMedia(index)}>
                                        <Ionicons name="close" size={16} color="white" />
                                    </TouchableOpacity>
                                </View>
                            ))}
                        </ScrollView>
                    )}
                </TouchableOpacity>

                {/* Choose Files Button */}
                <TouchableOpacity onPress={pickMedia} style={styles.chooseFilesBtn}>
                    <Ionicons name="cloud-upload-outline" size={18} color="#4b5563" />
                    <Text style={styles.chooseFilesText}>Choose Photos & Videos</Text>
                </TouchableOpacity>

                {/* Category Selector */}
                <View style={styles.formGroup}>
                    <Text style={styles.label}>Select Category</Text>
                    <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.categoryScroll}>
                        {CATEGORY_MAP.map((cat) => (
                            <TouchableOpacity
                                key={cat.name}
                                onPress={() => setCategory(cat.name)}
                                style={[
                                    styles.categoryBadge,
                                    category === cat.name ? styles.categoryBadgeSelected : {}
                                ]}
                                activeOpacity={0.8}
                            >
                                <Text style={styles.categoryIcon}>{cat.icon}</Text>
                                <Text style={[
                                    styles.categoryBadgeText,
                                    category === cat.name ? styles.categoryBadgeTextSelected : {}
                                ]}>
                                    {cat.name}
                                </Text>
                            </TouchableOpacity>
                        ))}
                    </ScrollView>
                </View>

                {/* AI Assistant Button */}
                <TouchableOpacity 
                    onPress={() => setAiModalVisible(true)}
                    style={{
                        flexDirection: 'row',
                        alignItems: 'center',
                        justifyContent: 'center',
                        backgroundColor: '#ea580c12',
                        borderWidth: 1.5,
                        borderColor: '#ea580c',
                        borderRadius: 24,
                        paddingVertical: 10,
                        paddingHorizontal: 16,
                        marginBottom: 20,
                        gap: 8,
                        shadowColor: '#ea580c',
                        shadowOffset: { width: 0, height: 2 },
                        shadowOpacity: 0.1,
                        shadowRadius: 4,
                        elevation: 1
                    }}
                    activeOpacity={0.8}
                >
                    <Ionicons name="sparkles" size={18} color="#ea580c" />
                    <Text style={{ fontSize: 14, fontWeight: 'bold', color: '#ea580c' }}>🤖 Generate Title & Description by AI</Text>
                </TouchableOpacity>

                {/* Form Fields */}
                <View style={styles.formGroup}>
                    <Text style={styles.label}>News Title</Text>
                    <TextInput
                        style={styles.input}
                        placeholder="Enter news title"
                        placeholderTextColor="#9ca3af"
                        value={title}
                        onChangeText={setTitle}
                    />
                </View>

                <View style={styles.formGroup}>
                    <Text style={styles.label}>News Description</Text>
                    <TextInput
                        style={[styles.input, styles.textArea]}
                        placeholder="Enter news description"
                        placeholderTextColor="#9ca3af"
                        multiline
                        numberOfLines={6}
                        value={description}
                        onChangeText={setDescription}
                        textAlignVertical="top"
                    />
                </View>

                {/* Consent Checkboxes */}
                <View style={{ marginVertical: 15, gap: 12 }}>
                    {/* Checkbox 1: Terms */}
                    <TouchableOpacity 
                        onPress={() => {
                            if (!termsAccepted) {
                                setTermsScrolledToBottom(false);
                                setTermsModalVisible(true);
                            } else {
                                setTermsAccepted(false);
                            }
                        }}
                        style={{ flexDirection: 'row', alignItems: 'center', gap: 10 }}
                        activeOpacity={0.8}
                    >
                        <View style={{
                            width: 20,
                            height: 20,
                            borderRadius: 4,
                            borderWidth: 1.5,
                            borderColor: '#ea580c',
                            backgroundColor: termsAccepted ? '#ea580c' : 'transparent',
                            justifyContent: 'center',
                            alignItems: 'center'
                        }}>
                            {termsAccepted && <Ionicons name="checkmark" size={14} color="white" />}
                        </View>
                        <Text style={{ fontSize: 13, color: '#374151', flex: 1 }}>
                            I agree to the <Text style={{ fontWeight: 'bold', color: '#ea580c', decorationLine: 'underline' }}>Disclaimer & Terms and Conditions</Text>
                        </Text>
                    </TouchableOpacity>

                    {/* Checkbox 2: Policy */}
                    <TouchableOpacity 
                        onPress={() => {
                            if (!policyAccepted) {
                                setPolicyScrolledToBottom(false);
                                setPolicyModalVisible(true);
                            } else {
                                setPolicyAccepted(false);
                            }
                        }}
                        style={{ flexDirection: 'row', alignItems: 'center', gap: 10 }}
                        activeOpacity={0.8}
                    >
                        <View style={{
                            width: 20,
                            height: 20,
                            borderRadius: 4,
                            borderWidth: 1.5,
                            borderColor: '#ea580c',
                            backgroundColor: policyAccepted ? '#ea580c' : 'transparent',
                            justifyContent: 'center',
                            alignItems: 'center'
                        }}>
                            {policyAccepted && <Ionicons name="checkmark" size={14} color="white" />}
                        </View>
                        <Text style={{ fontSize: 13, color: '#374151', flex: 1 }}>
                            I agree to the <Text style={{ fontWeight: 'bold', color: '#ea580c', decorationLine: 'underline' }}>Privacy Policy</Text>
                        </Text>
                    </TouchableOpacity>
                </View>

                {/* Submit Button */}
                <TouchableOpacity 
                    onPress={handleSubmit} 
                    disabled={loading} 
                    style={[styles.submitBtn, loading ? styles.submitBtnDisabled : {}]}
                    activeOpacity={0.8}
                >
                    {loading ? (
                        <ActivityIndicator color="white" />
                    ) : (
                        <Text style={styles.submitBtnText}>Submit News</Text>
                    )}
                </TouchableOpacity>
            </ScrollView>

            {/* AI Assistant Modal */}
            <Modal
                visible={aiModalVisible}
                animationType="slide"
                transparent={true}
                onRequestClose={() => setAiModalVisible(false)}
            >
                <Pressable 
                    style={{ flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' }}
                    onPress={() => setAiModalVisible(false)}
                >
                    <View style={{ backgroundColor: 'white', borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 20, maxHeight: '80%' }}>
                        {/* Handle bar */}
                        <View style={{ alignSelf: 'center', width: 40, height: 4, borderRadius: 2, backgroundColor: '#e5e7eb', marginBottom: 16 }} />
                        
                        {/* Header */}
                        <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 18 }}>
                            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                                <Ionicons name="sparkles" size={20} color="#ea580c" />
                                <Text style={{ fontSize: 18, fontWeight: 'bold', color: '#111827' }}>AI News Assistant</Text>
                            </View>
                            <TouchableOpacity onPress={() => setAiModalVisible(false)}>
                                <Ionicons name="close-circle" size={24} color="#9ca3af" />
                            </TouchableOpacity>
                        </View>

                        <ScrollView showsVerticalScrollIndicator={false} keyboardShouldPersistTaps="handled">
                            <Text style={{ fontSize: 14, fontWeight: '600', color: '#374151', marginBottom: 8 }}>
                                Enter topic keywords / मुख्य शब्द दर्ज करें:
                            </Text>
                            
                            <TextInput
                                style={{
                                    borderWidth: 1,
                                    borderColor: '#ea580c',
                                    borderRadius: 12,
                                    paddingHorizontal: 14,
                                    paddingVertical: 12,
                                    fontSize: 15,
                                    color: '#1f2937',
                                    backgroundColor: '#fff7ed',
                                    marginBottom: 16,
                                    textAlignVertical: 'top',
                                    height: 80
                                }}
                                placeholder="जैसे: दिल्ली में बारिश, खेल प्रतियोगिता जीत, सड़क हादसा आदि..."
                                placeholderTextColor="#fdba74"
                                value={aiKeywords}
                                onChangeText={setAiKeywords}
                                multiline
                            />

                            <Text style={{ fontSize: 12, color: '#6b7280', marginBottom: 20 }}>
                                Selected Category / चयनित श्रेणी: <Text style={{ fontWeight: 'bold', color: '#ea580c' }}>{category}</Text>
                            </Text>

                            <TouchableOpacity 
                                onPress={handleAiGenerate}
                                disabled={generating}
                                style={{
                                    backgroundColor: '#ea580c',
                                    paddingVertical: 14,
                                    borderRadius: 24,
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    flexDirection: 'row',
                                    gap: 8,
                                    shadowColor: '#ea580c',
                                    shadowOffset: { width: 0, height: 4 },
                                    shadowOpacity: 0.2,
                                    shadowRadius: 6,
                                    elevation: 3
                                }}
                                activeOpacity={0.8}
                            >
                                {generating ? (
                                    <ActivityIndicator color="white" />
                                ) : (
                                    <>
                                        <Ionicons name="flash" size={18} color="white" />
                                        <Text style={{ color: 'white', fontWeight: 'bold', fontSize: 15 }}>Generate Draft / ड्राफ्ट लिखें</Text>
                                    </>
                                )}
                            </TouchableOpacity>
                        </ScrollView>
                    </View>
                </Pressable>
            </Modal>

            {/* Terms & Disclaimer Modal */}
            <Modal
                visible={termsModalVisible}
                animationType="slide"
                transparent={true}
                onRequestClose={() => setTermsModalVisible(false)}
            >
                <View style={{ flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'center', padding: 20 }}>
                    <View style={{ backgroundColor: 'white', borderRadius: 24, flex: 0.85, padding: 20, shadowColor: '#000', shadowOffset: { width: 0, height: 10 }, shadowOpacity: 0.15, shadowRadius: 16, elevation: 10 }}>
                        <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 }}>
                            <Text style={{ fontSize: 18, fontWeight: 'bold', color: '#111827' }}>
                                Disclaimer & Terms
                            </Text>
                            <TouchableOpacity onPress={() => setTermsModalVisible(false)}>
                                <Ionicons name="close" size={24} color="#6b7280" />
                            </TouchableOpacity>
                        </View>
                        
                        <ScrollView 
                            style={{ flex: 1, borderBottomWidth: 1, borderBottomColor: '#f3f4f6', marginBottom: 15 }}
                            onScroll={(e) => handleScroll(e, setTermsScrolledToBottom)}
                            scrollEventThrottle={16}
                            showsVerticalScrollIndicator={true}
                        >
                            <Text style={{ fontSize: 14, fontWeight: 'bold', color: '#ea580c', marginBottom: 8 }}>Disclaimer</Text>
                            <Text style={{ fontSize: 13, color: '#4b5563', lineHeight: 18, marginBottom: 16 }}>
                                LinkUp is a User-Generated Content Platform.{"\n\n"}
                                All news, posts, reels, photos, videos, matrimony profiles, comments, chat messages, voice calls, and video calls available on the platform are created, uploaded, or shared by users.{"\n\n"}
                                The respective user is solely responsible for the accuracy, legality, authenticity, ownership, and copyright compliance of the content they publish.{"\n\n"}
                                LinkUp, its owners, developers, employees, administrators, and affiliates shall not be held responsible or liable for any user-generated content, copyright infringement, false information, defamatory material, or illegal activities conducted by users.{"\n\n"}
                                Upon receiving a valid complaint or legal notice, LinkUp reserves the right to remove content, restrict access, suspend accounts, or take appropriate action without prior notice.{"\n\n"}
                                The views and opinions expressed by users do not represent the views of LinkUp.
                            </Text>

                            <Text style={{ fontSize: 14, fontWeight: 'bold', color: '#ea580c', marginBottom: 8, marginTop: 8 }}>Terms and Conditions</Text>
                            <Text style={{ fontSize: 11, color: '#9ca3af', marginBottom: 12 }}>Last Updated: June 25, 2026</Text>
                            <Text style={{ fontSize: 13, color: '#4b5563', lineHeight: 18, marginBottom: 16 }}>
                                By using LinkUp, you agree to the following terms and conditions:{"\n\n"}
                                <Text style={{ fontWeight: 'bold', color: '#1f2937' }}>1. User Responsibility</Text>{"\n"}
                                Users are solely responsible for all content uploaded, posted, shared, or transmitted through the platform.{"\n\n"}
                                <Text style={{ fontWeight: 'bold', color: '#1f2937' }}>2. Prohibited Content</Text>{"\n"}
                                Users must not upload, publish, or distribute:{"\n"}
                                • Copyright-infringing content{"\n"}
                                • False or misleading news{"\n"}
                                • Obscene, abusive, or offensive material{"\n"}
                                • Hate speech or discriminatory content{"\n"}
                                • Illegal or harmful content{"\n"}
                                • Defamatory material{"\n\n"}
                                <Text style={{ fontWeight: 'bold', color: '#1f2937' }}>3. News Section</Text>{"\n"}
                                Users are responsible for the accuracy and legality of news content they publish.{"\n\n"}
                                <Text style={{ fontWeight: 'bold', color: '#1f2937' }}>4. Matrimony Section</Text>{"\n"}
                                • Users are responsible for the accuracy of their profiles.{"\n"}
                                • LinkUp does not guarantee any marriage proposal, relationship, or match.{"\n"}
                                • Users interact with others at their own risk.{"\n\n"}
                                <Text style={{ fontWeight: 'bold', color: '#1f2937' }}>5. Chat, Group Chat, Voice Call & Video Call</Text>{"\n"}
                                Users are solely responsible for their communications and interactions on the platform.{"\n\n"}
                                <Text style={{ fontWeight: 'bold', color: '#1f2937' }}>6. Account Suspension</Text>{"\n"}
                                LinkUp reserves the right to suspend or terminate any account that violates these Terms.{"\n\n"}
                                <Text style={{ fontWeight: 'bold', color: '#1f2937' }}>7. Limitation of Liability</Text>{"\n"}
                                LinkUp shall not be liable for any loss, damage, dispute, or legal claim arising from user-generated content or user interactions.{"\n\n"}
                                <Text style={{ fontWeight: 'bold', color: '#1f2937' }}>8. Modification of Services</Text>{"\n"}
                                LinkUp may modify, suspend, or discontinue any service without prior notice.
                            </Text>
                        </ScrollView>

                        {/* Button control */}
                        {termsScrolledToBottom ? (
                            <TouchableOpacity
                                onPress={() => {
                                    setTermsAccepted(true);
                                    setTermsModalVisible(false);
                                }}
                                style={{
                                    backgroundColor: '#ea580c',
                                    paddingVertical: 14,
                                    borderRadius: 24,
                                    alignItems: 'center',
                                    flexDirection: 'row',
                                    justifyContent: 'center',
                                    gap: 6
                                }}
                            >
                                <Ionicons name="checkmark-circle-outline" size={18} color="white" />
                                <Text style={{ color: 'white', fontWeight: 'bold', fontSize: 15 }}>Accept and Continue</Text>
                            </TouchableOpacity>
                        ) : (
                            <View
                                style={{
                                    backgroundColor: '#f3f4f6',
                                    paddingVertical: 14,
                                    borderRadius: 24,
                                    alignItems: 'center',
                                    flexDirection: 'row',
                                    justifyContent: 'center',
                                    gap: 6
                                }}
                            >
                                <Ionicons name="arrow-down-circle-outline" size={18} color="#9ca3af" />
                                <Text style={{ color: '#9ca3af', fontWeight: 'bold', fontSize: 13 }}>Scroll down to accept / नीचे तक स्क्रॉल करें</Text>
                            </View>
                        )}
                    </View>
                </View>
            </Modal>

            {/* Privacy Policy Modal */}
            <Modal
                visible={policyModalVisible}
                animationType="slide"
                transparent={true}
                onRequestClose={() => setPolicyModalVisible(false)}
            >
                <View style={{ flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'center', padding: 20 }}>
                    <View style={{ backgroundColor: 'white', borderRadius: 24, flex: 0.85, padding: 20, shadowColor: '#000', shadowOffset: { width: 0, height: 10 }, shadowOpacity: 0.15, shadowRadius: 16, elevation: 10 }}>
                        <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 }}>
                            <Text style={{ fontSize: 18, fontWeight: 'bold', color: '#111827' }}>
                                Privacy Policy
                            </Text>
                            <TouchableOpacity onPress={() => setPolicyModalVisible(false)}>
                                <Ionicons name="close" size={24} color="#6b7280" />
                            </TouchableOpacity>
                        </View>
                        
                        <ScrollView 
                            style={{ flex: 1, borderBottomWidth: 1, borderBottomColor: '#f3f4f6', marginBottom: 15 }}
                            onScroll={(e) => handleScroll(e, setPolicyScrolledToBottom)}
                            scrollEventThrottle={16}
                            showsVerticalScrollIndicator={true}
                        >
                            <Text style={{ fontSize: 14, fontWeight: 'bold', color: '#ea580c', marginBottom: 8 }}>Privacy Policy</Text>
                            <Text style={{ fontSize: 11, color: '#9ca3af', marginBottom: 12 }}>Last Updated: June 25, 2026</Text>
                            <Text style={{ fontSize: 13, color: '#4b5563', lineHeight: 18, marginBottom: 16 }}>
                                Welcome to LinkUp. LinkUp is a Social Media, News, Matrimony, Chat, Group Chat, Voice Call, and Video Call platform. We respect your privacy and are committed to protecting your personal information.{"\n\n"}
                                <Text style={{ fontWeight: 'bold', color: '#1f2937' }}>1. Information We Collect</Text>{"\n"}
                                • Name, mobile number, email address{"\n"}
                                • Profile photos and profile information{"\n"}
                                • News, posts, reels, photos, videos, comments{"\n"}
                                • Matrimony profile information{"\n"}
                                • Chat and group chat information{"\n"}
                                • Voice call and video call related technical data{"\n"}
                                • Device information, IP address, and log data{"\n\n"}
                                <Text style={{ fontWeight: 'bold', color: '#1f2937' }}>2. How We Use Information</Text>{"\n"}
                                • To create and manage user accounts{"\n"}
                                • To provide social media, news, and matrimony services{"\n"}
                                • To enable chat, voice call, and video call features{"\n"}
                                • To improve platform performance and user experience{"\n"}
                                • To maintain security and prevent fraud{"\n\n"}
                                <Text style={{ fontWeight: 'bold', color: '#1f2937' }}>3. Information Sharing</Text>{"\n"}
                                We do not sell users' personal information. Information may be shared if required by law, court order, or government authority.{"\n\n"}
                                <Text style={{ fontWeight: 'bold', color: '#1f2937' }}>4. Data Security</Text>{"\n"}
                                We implement reasonable security measures to protect user data. However, no internet-based service can guarantee 100% security.{"\n\n"}
                                <Text style={{ fontWeight: 'bold', color: '#1f2937' }}>5. Children's Privacy</Text>{"\n"}
                                Users under the age of 18 are not permitted to use Matrimony services.{"\n\n"}
                                <Text style={{ fontWeight: 'bold', color: '#1f2937' }}>6. Changes to Policy</Text>{"\n"}
                                LinkUp reserves the right to modify this Privacy Policy at any time.
                            </Text>
                        </ScrollView>

                        {/* Button control */}
                        {policyScrolledToBottom ? (
                            <TouchableOpacity
                                onPress={() => {
                                    setPolicyAccepted(true);
                                    setPolicyModalVisible(false);
                                }}
                                style={{
                                    backgroundColor: '#ea580c',
                                    paddingVertical: 14,
                                    borderRadius: 24,
                                    alignItems: 'center',
                                    flexDirection: 'row',
                                    justifyContent: 'center',
                                    gap: 6
                                }}
                            >
                                <Ionicons name="checkmark-circle-outline" size={18} color="white" />
                                <Text style={{ color: 'white', fontWeight: 'bold', fontSize: 15 }}>Accept and Continue</Text>
                            </TouchableOpacity>
                        ) : (
                            <View
                                style={{
                                    backgroundColor: '#f3f4f6',
                                    paddingVertical: 14,
                                    borderRadius: 24,
                                    alignItems: 'center',
                                    flexDirection: 'row',
                                    justifyContent: 'center',
                                    gap: 6
                                }}
                            >
                                <Ionicons name="arrow-down-circle-outline" size={18} color="#9ca3af" />
                                <Text style={{ color: '#9ca3af', fontWeight: 'bold', fontSize: 13 }}>Scroll down to accept / नीचे तक स्क्रॉल करें</Text>
                            </View>
                        )}
                    </View>
                </View>
            </Modal>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: 16,
        paddingVertical: 12,
        borderBottomWidth: 1,
        borderBottomColor: '#f3f4f6',
        backgroundColor: 'white',
    },
    backBtn: {
        padding: 4,
    },
    headerTitle: {
        fontSize: 18,
        fontWeight: 'bold',
        color: '#1f2937',
    },
    scrollContainer: {
        padding: 20,
        paddingBottom: 40,
    },
    sectionTitle: {
        fontSize: 14,
        fontWeight: '600',
        color: '#374151',
        marginBottom: 8,
    },
    uploadPlaceholder: {
        width: '100%',
        height: 150,
        borderWidth: 2,
        borderColor: '#d1d5db',
        borderStyle: 'dashed',
        borderRadius: 12,
        backgroundColor: '#f9fafb',
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: 12,
        overflow: 'hidden',
    },
    uploadPlaceholderWithImages: {
        borderStyle: 'solid',
        borderColor: '#e5e7eb',
        height: 120,
    },
    uploadPlaceholderInner: {
        alignItems: 'center',
    },
    uploadText: {
        color: '#6b7280',
        fontSize: 13,
        marginTop: 6,
    },
    previewsScroll: {
        padding: 10,
        width: '100%',
    },
    imageItemContainer: {
        width: 100,
        height: 100,
        marginRight: 10,
        borderRadius: 8,
        overflow: 'hidden',
        position: 'relative',
        borderWidth: 1,
        borderColor: '#e5e7eb',
    },
    imagePreview: {
        width: '100%',
        height: '100%',
    },
    removeImageBtn: {
        position: 'absolute',
        top: 4,
        right: 4,
        backgroundColor: 'rgba(0,0,0,0.6)',
        padding: 4,
        borderRadius: 12,
    },
    chooseFilesBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        paddingVertical: 10,
        borderWidth: 1,
        borderColor: '#d1d5db',
        borderRadius: 8,
        backgroundColor: 'white',
        gap: 8,
        marginBottom: 20,
    },
    chooseFilesText: {
        fontSize: 13,
        fontWeight: '500',
        color: '#374151',
    },
    formGroup: {
        marginBottom: 20,
    },
    label: {
        fontSize: 14,
        fontWeight: '600',
        color: '#374151',
        marginBottom: 6,
    },
    input: {
        borderWidth: 1,
        borderColor: '#d1d5db',
        borderRadius: 8,
        paddingHorizontal: 14,
        paddingVertical: 12,
        fontSize: 15,
        color: '#1f2937',
        backgroundColor: '#fafafa',
    },
    textArea: {
        height: 130,
    },
    submitBtn: {
        backgroundColor: '#ea580c',
        paddingVertical: 14,
        borderRadius: 8,
        alignItems: 'center',
        marginTop: 10,
        shadowColor: '#ea580c',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.1,
        shadowRadius: 6,
        elevation: 2,
    },
    submitBtnDisabled: {
        opacity: 0.7,
    },
    submitBtnText: {
        color: 'white',
        fontWeight: 'bold',
        fontSize: 15,
    },
    categoryScroll: {
        flexDirection: 'row',
        paddingVertical: 5,
    },
    categoryBadge: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#f3f4f6',
        borderRadius: 25,
        paddingHorizontal: 16,
        paddingVertical: 10,
        marginRight: 10,
        borderWidth: 0,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 1 },
        shadowOpacity: 0.08,
        shadowRadius: 2,
        elevation: 1,
    },
    categoryBadgeSelected: {
        backgroundColor: '#ea580c',
        shadowColor: '#ea580c',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.25,
        shadowRadius: 6,
        elevation: 4,
    },
    categoryIcon: {
        marginRight: 6,
        fontSize: 14,
    },
    categoryBadgeText: {
        fontSize: 12,
        fontWeight: '700',
        color: '#374151',
    },
    categoryBadgeTextSelected: {
        color: 'white',
    },
});

export default CreateNewsScreen;
