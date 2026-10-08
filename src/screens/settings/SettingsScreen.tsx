import React, { useState } from 'react';
import { View, Text, TouchableOpacity, ScrollView, Alert, Linking, ActionSheetIOS, Platform, Modal, Pressable } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useLanguage } from '../../context/LanguageContext';
import { useFont } from '../../context/FontContext';

const SettingsScreen = ({ navigation }: any) => {
    const { language, setLanguage, t } = useLanguage();
    const { fontFamily, setFontFamily } = useFont();
    const [fontModalVisible, setFontModalVisible] = useState(false);
    const [langModalVisible, setLangModalVisible] = useState(false);

    const handleLogout = async () => {
        Alert.alert(t('logout'), t('logoutConfirm'), [
            { text: t('cancel'), style: "cancel" },
            {
                text: t('logout'),
                style: "destructive",
                onPress: async () => {
                    await AsyncStorage.removeItem('user');
                    navigation.reset({
                        index: 0,
                        routes: [{ name: 'Auth' }],
                    });
                }
            }
        ]);
    };

    const handleChangeLanguage = () => {
        setLangModalVisible(true);
    };

    const handleChangeFont = () => {
        setFontModalVisible(true);
    };

    const getFontLabel = (font: string) => {
        const isHi = language === 'hi';
        const isGu = language === 'gu';
        switch (font) {
            case 'System': return isHi ? 'सिस्टम डिफ़ॉल्ट' : isGu ? 'સિસ્ટમ ડિફોલ્ટ' : 'System Default';
            case 'Devnagari Noto': return isHi ? 'देवनागरी स्पष्ट' : isGu ? 'દેવનાગરી સ્પષ્ટ' : 'Devanagari Clean';
            case 'Devnagari Rozha': return isHi ? 'देवनागरी कलात्मक' : isGu ? 'દેવનાગરી કલાત્મક' : 'Devanagari Stylised';
            case 'Devnagari Yatra': return isHi ? 'देवनागरी आर्ट' : isGu ? 'દેવનાગરી આર્ટ' : 'Devanagari Art';
            case 'Serif': return isHi ? 'सेरिफ़ क्लासिक' : isGu ? 'સેરિફ ક્લાસિક' : 'Serif Classic';
            case 'Monospace': return isHi ? 'मोनोस्पेस कोड' : isGu ? 'મોનોસ્પેસ કોડ' : 'Monospace Code';
            case 'Condensed': return isHi ? 'कंडेंस्ड सांस' : isGu ? 'કન્ડેન્સ્ડ સાંસ' : 'Condensed Sans';
            case 'Light': return isHi ? 'लाइट मॉडर्न' : isGu ? 'લાઇટ મોર્ડન' : 'Light Modern';
            default: return font;
        }
    };

    const openLink = (url: string) => {
        Linking.openURL(url).catch(err => console.error("Couldn't load page", err));
    };

    interface MenuItem {
        label: string;
        icon: string;
        action: () => void;
        color?: string;
        value?: string;
    }

    interface Section {
        title: string;
        items: MenuItem[];
    }

    const sections: Section[] = [
        {
            title: t('language'),
            items: [
                {
                    label: t('changeLanguage'),
                    icon: "language-outline",
                    action: handleChangeLanguage,
                    value: language === 'en' ? 'English' : language === 'hi' ? 'हिन्दी' : 'ગુજરાતી'
                }
            ]
        },
        {
            title: "Appearance",
            items: [
                {
                    label: "Change Font Family",
                    icon: "text-outline",
                    action: handleChangeFont,
                    value: getFontLabel(fontFamily)
                }
            ]
        },
        {
            title: "Account",
            items: [
                { label: t('editProfile'), icon: "person-outline", action: () => navigation.navigate('EditProfile') },
                { label: "Change Password", icon: "lock-closed-outline", action: () => navigation.navigate('ChangePassword') },
            ]
        },
        {
            title: "Support & Legal",
            items: [
                { label: "Privacy Policy", icon: "shield-checkmark-outline", action: () => openLink('https://sadhu-vandana.com/privacy-policy') },
                { label: "Terms & Conditions", icon: "document-text-outline", action: () => openLink('https://sadhu-vandana.com/terms') },
                { label: "Contact Us", icon: "mail-outline", action: () => openLink('mailto:support@sadhu-vandana.com') },
            ]
        },
        {
            title: "Actions",
            items: [
                { label: t('logout'), icon: "log-out-outline", action: handleLogout, color: '#ef4444' },
            ]
        }
    ];

    return (
        <SafeAreaView style={{ flex: 1, backgroundColor: 'white' }}>
            <View style={{ flexDirection: 'row', alignItems: 'center', padding: 16, borderBottomWidth: 1, borderBottomColor: '#f3f4f6' }}>
                <TouchableOpacity onPress={() => navigation.goBack()}>
                    <Ionicons name="arrow-back" size={24} color="black" />
                </TouchableOpacity>
                <Text style={{ fontSize: 20, fontWeight: 'bold', marginLeft: 16 }}>{t('settings')}</Text>
            </View>

            <ScrollView contentContainerStyle={{ padding: 16 }}>
                {sections.map((section, idx) => (
                    <View key={idx} style={{ marginBottom: 24 }}>
                        <Text style={{ fontSize: 14, fontWeight: 'bold', color: '#9ca3af', marginBottom: 8, textTransform: 'uppercase' }}>
                            {section.title}
                        </Text>
                        <View style={{ backgroundColor: '#fff', borderRadius: 12, overflow: 'hidden', borderWidth: 1, borderColor: '#f3f4f6' }}>
                            {section.items.map((item, i) => (
                                <TouchableOpacity
                                    key={i}
                                    onPress={item.action}
                                    style={{
                                        flexDirection: 'row', alignItems: 'center', padding: 16,
                                        borderBottomWidth: i === section.items.length - 1 ? 0 : 1,
                                        borderBottomColor: '#f3f4f6'
                                    }}
                                >
                                    <View style={{ width: 32, alignItems: 'center', marginRight: 12 }}>
                                        <Ionicons name={item.icon as any} size={22} color={item.color || '#4b5563'} />
                                    </View>
                                    <Text style={{ fontSize: 16, color: item.color || '#1f2937', flex: 1 }}>{item.label}</Text>

                                    {item.value && (
                                        <Text style={{ marginRight: 8, color: '#ea580c', fontWeight: 'bold' }}>{item.value}</Text>
                                    )}

                                    <Ionicons name="chevron-forward" size={20} color="#d1d5db" />
                                </TouchableOpacity>
                            ))}
                        </View>
                    </View>
                ))}

            </ScrollView>

            {/* Premium Font Selector Bottom Sheet Modal */}
            <Modal
                visible={fontModalVisible}
                animationType="slide"
                transparent={true}
                onRequestClose={() => setFontModalVisible(false)}
            >
                <Pressable 
                    style={{ flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' }}
                    onPress={() => setFontModalVisible(false)}
                >
                    <View style={{ backgroundColor: 'white', borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 20, maxHeight: '80%' }}>
                        {/* Handle bar */}
                        <View style={{ alignSelf: 'center', width: 40, height: 4, borderRadius: 2, backgroundColor: '#e5e7eb', marginBottom: 16 }} />
                        
                        {/* Header */}
                        <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 18 }}>
                            <Text style={{ fontSize: 18, fontWeight: 'bold', color: '#111827' }}>
                                {language === 'hi' ? 'फ़ॉन्ट शैली चुनें' : language === 'gu' ? 'ફોન્ટ શૈલી પસંદ કરો' : 'Choose Font Style'}
                            </Text>
                            <TouchableOpacity onPress={() => setFontModalVisible(false)}>
                                <Ionicons name="close-circle" size={24} color="#9ca3af" />
                            </TouchableOpacity>
                        </View>

                        <ScrollView showsVerticalScrollIndicator={false}>
                            <View style={{ gap: 10 }}>
                                {[
                                    { 
                                        key: 'System', 
                                        label: language === 'hi' ? 'सिस्टम डिफ़ॉल्ट' : language === 'gu' ? 'સિસ્ટમ ડિફોલ્ટ' : 'System Default', 
                                        fontStyle: undefined, 
                                        description: language === 'hi' ? 'डिवाइस का मुख्य फ़ॉन्ट' : language === 'gu' ? 'ડિવાઇસના મુખ્ય ફોન્ટ' : 'Default system typography' 
                                    },
                                    { 
                                        key: 'Devnagari Noto', 
                                        label: language === 'hi' ? 'देवनागरी स्पष्ट (Noto Sans)' : language === 'gu' ? 'દેવનાગરી સ્પષ્ટ (Noto Sans)' : 'Devanagari Clean', 
                                        fontStyle: 'NotoSansDevanagari', 
                                        description: language === 'hi' ? 'साफ़ और पढ़ने में आसान देवनागरी लेख' : language === 'gu' ? 'સાફ અને વાંચવામાં સરળ દેવનાગરી લેખ' : 'Clean Noto Sans Devanagari text' 
                                    },
                                    { 
                                        key: 'Devnagari Rozha', 
                                        label: language === 'hi' ? 'देवनागरी कलात्मक (Rozha One)' : language === 'gu' ? 'દેવનાગરી કલાત્મક (Rozha One)' : 'Devanagari Stylised', 
                                        fontStyle: 'RozhaOne', 
                                        description: language === 'hi' ? 'मोटे और सुंदर डिज़ाइनर शीर्षक' : language === 'gu' ? 'જાડા અને સુંદર ડિઝાઇનર શીર્ષક' : 'Bold artistic Rozha One headings' 
                                    },
                                    { 
                                        key: 'Devnagari Yatra', 
                                        label: language === 'hi' ? 'देवनागरी आर्ट (Yatra One)' : language === 'gu' ? 'દેવનાગરી આર્ટ (Yatra One)' : 'Devanagari Art', 
                                        fontStyle: 'YatraOne', 
                                        description: language === 'hi' ? 'पारंपरिक देवनागरी सुलेख' : language === 'gu' ? 'પરંપરાગત દેવનાગરી સુલેખન' : 'Classic Yatra One calligraphy' 
                                    },
                                    { 
                                        key: 'Serif', 
                                        label: language === 'hi' ? 'सेरिफ़ क्लासिक (Serif)' : language === 'gu' ? 'સેરિફ ક્લાસિક (Serif)' : 'Serif Classic', 
                                        fontStyle: Platform.OS === 'android' ? 'serif' : 'Georgia', 
                                        description: language === 'hi' ? 'क्लासिक और गंभीर लिखावट' : language === 'gu' ? 'ક્લાસિક અને ગંભીર લખાણ' : 'Classic elegant Georgia serif font' 
                                    },
                                    { 
                                        key: 'Monospace', 
                                        label: language === 'hi' ? 'मोनोस्पेस कोड (Monospace)' : language === 'gu' ? 'મોનોસ્પેસ કોડ (Monospace)' : 'Monospace Code', 
                                        fontStyle: Platform.OS === 'android' ? 'monospace' : 'Courier', 
                                        description: language === 'hi' ? 'टाइपराइटर जैसे समान आकार के अक्षर' : language === 'gu' ? 'ટાઈપરાઈટર જેવા સમાન કદના અક્ષરો' : 'Clean typewriter coding font' 
                                    },
                                    { 
                                        key: 'Condensed', 
                                        label: language === 'hi' ? 'कंडेंस्ड सांस (Condensed)' : language === 'gu' ? 'કન્ડેન્સ્ડ સાંસ (Condensed)' : 'Condensed Sans', 
                                        fontStyle: Platform.OS === 'android' ? 'sans-serif-condensed' : 'Arial', 
                                        description: language === 'hi' ? 'सघन और पतली लिखावट' : language === 'gu' ? 'સઘન અને પાતળું લખાણ' : 'Sleek compact Arial sans-serif' 
                                    },
                                    { 
                                        key: 'Light', 
                                        label: language === 'hi' ? 'लाइट मॉडर्न (Light)' : language === 'gu' ? 'લાઇટ મોર્ડન (Light)' : 'Light Modern', 
                                        fontStyle: Platform.OS === 'android' ? 'sans-serif-light' : 'Helvetica-Light', 
                                        description: language === 'hi' ? 'पतले और आधुनिक अक्षर' : language === 'gu' ? 'પાતળા અને આધુનિક અક્ષરો' : 'Thin minimal modern typography' 
                                    },
                                ].map((opt) => {
                                    const isSelected = fontFamily === opt.key;
                                    return (
                                        <TouchableOpacity
                                            key={opt.key}
                                            onPress={() => {
                                                setFontFamily(opt.key);
                                                setFontModalVisible(false);
                                            }}
                                            style={{
                                                flexDirection: 'row',
                                                alignItems: 'center',
                                                padding: 14,
                                                borderRadius: 16,
                                                borderWidth: 1,
                                                borderColor: isSelected ? '#ea580c' : '#f3f4f6',
                                                backgroundColor: isSelected ? '#fff7ed' : 'white',
                                            }}
                                            activeOpacity={0.7}
                                        >
                                            <View style={{ flex: 1 }}>
                                                {/* Preview label styled in the actual font! */}
                                                <Text style={{
                                                    fontSize: 16,
                                                    fontWeight: 'bold',
                                                    color: isSelected ? '#ea580c' : '#1f2937',
                                                    fontFamily: opt.fontStyle
                                                }}>
                                                    {opt.label}
                                                </Text>
                                                <Text style={{ fontSize: 11, color: isSelected ? '#c2410c' : '#9ca3af', marginTop: 2 }}>
                                                    {opt.description}
                                                </Text>
                                            </View>
                                            {isSelected && (
                                                <Ionicons name="checkmark-circle" size={22} color="#ea580c" />
                                            )}
                                        </TouchableOpacity>
                                    );
                                })}
                            </View>
                        </ScrollView>
                    </View>
                </Pressable>
            </Modal>

            {/* Premium Language Selector Bottom Sheet Modal */}
            <Modal
                visible={langModalVisible}
                animationType="slide"
                transparent={true}
                onRequestClose={() => setLangModalVisible(false)}
            >
                <Pressable 
                    style={{ flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' }}
                    onPress={() => setLangModalVisible(false)}
                >
                    <View style={{ backgroundColor: 'white', borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 20, maxHeight: '80%' }}>
                        {/* Handle bar */}
                        <View style={{ alignSelf: 'center', width: 40, height: 4, borderRadius: 2, backgroundColor: '#e5e7eb', marginBottom: 16 }} />
                        
                        {/* Header */}
                        <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 18 }}>
                            <Text style={{ fontSize: 18, fontWeight: 'bold', color: '#111827' }}>
                                {language === 'hi' ? 'भाषा चुनें' : language === 'gu' ? 'ભાષા પસંદ કરો' : 'Choose Language'}
                            </Text>
                            <TouchableOpacity onPress={() => setLangModalVisible(false)}>
                                <Ionicons name="close-circle" size={24} color="#9ca3af" />
                            </TouchableOpacity>
                        </View>

                        <ScrollView showsVerticalScrollIndicator={false}>
                            <View style={{ gap: 10 }}>
                                {[
                                    { key: 'en', label: 'English', nativeLabel: 'English', desc: 'Use app in English' },
                                    { key: 'hi', label: 'हिन्दी', nativeLabel: 'Hindi', desc: 'ऐप का हिंदी में उपयोग करें' },
                                    { key: 'gu', label: 'ગુજરાતી', nativeLabel: 'Gujarati', desc: 'ગુજરાતીમાં એપ્લિકેશનનો ઉપયોગ કરો' },
                                ].map((opt) => {
                                    const isSelected = language === opt.key;
                                    return (
                                        <TouchableOpacity
                                            key={opt.key}
                                            onPress={() => {
                                                setLanguage(opt.key);
                                                setLangModalVisible(false);
                                            }}
                                            style={{
                                                flexDirection: 'row',
                                                alignItems: 'center',
                                                padding: 16,
                                                borderRadius: 16,
                                                borderWidth: 1,
                                                borderColor: isSelected ? '#ea580c' : '#f3f4f6',
                                                backgroundColor: isSelected ? '#fff7ed' : 'white',
                                            }}
                                            activeOpacity={0.7}
                                        >
                                            <View style={{ flex: 1 }}>
                                                <Text style={{
                                                    fontSize: 16,
                                                    fontWeight: 'bold',
                                                    color: isSelected ? '#ea580c' : '#1f2937'
                                                }}>
                                                    {opt.label} <Text style={{ fontSize: 12, fontWeight: 'normal', color: '#6b7280' }}>({opt.nativeLabel})</Text>
                                                </Text>
                                                <Text style={{ fontSize: 11, color: isSelected ? '#c2410c' : '#9ca3af', marginTop: 2 }}>
                                                    {opt.desc}
                                                </Text>
                                            </View>
                                            {isSelected && (
                                                <Ionicons name="checkmark-circle" size={22} color="#ea580c" />
                                            )}
                                        </TouchableOpacity>
                                    );
                                })}
                            </View>
                        </ScrollView>
                    </View>
                </Pressable>
            </Modal>
        </SafeAreaView>
    );
};

export default SettingsScreen;
