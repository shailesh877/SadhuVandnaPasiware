import React from 'react';
import { View, Text, TouchableOpacity, ScrollView, Alert, Linking, ActionSheetIOS, Platform } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useLanguage } from '../../context/LanguageContext';

const SettingsScreen = () => {
    const navigation = useNavigation<any>();
    const { language, setLanguage, t } = useLanguage();

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
        Alert.alert(t('changeLanguage'), t('language'), [
            { text: 'English', onPress: () => setLanguage('en') },
            { text: 'हिन्दी', onPress: () => setLanguage('hi') },
            { text: 'ગુજરાતી', onPress: () => setLanguage('gu') },
            { text: t('cancel'), style: 'cancel' }
        ]);
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

                <View style={{ alignItems: 'center', marginTop: 20 }}>
                    <Text style={{ color: '#9ca3af', fontSize: 12 }}>App Version 1.0.0</Text>
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

export default SettingsScreen;
