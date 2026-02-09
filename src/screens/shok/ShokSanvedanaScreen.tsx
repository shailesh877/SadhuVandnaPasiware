import React from 'react';
import { View, Text, TouchableOpacity, Image, Linking, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useLanguage } from '../../context/LanguageContext';

const WEBSITE_URL = "https://sadhuvandna.co.in/shok_sanvedana.php";

const ShokSanvedanaScreen = ({ navigation }: any) => {
    const { t } = useLanguage();

    const openWebsite = async () => {
        try {
            const supported = await Linking.canOpenURL(WEBSITE_URL);
            if (supported) {
                await Linking.openURL(WEBSITE_URL);
            } else {
                Alert.alert(t('error'), "Cannot open website URL on this device.");
            }
        } catch (error) {
            console.error("Failed to open URL", error);
            Alert.alert(t('error'), "Failed to open website.");
        }
    };

    return (
        <SafeAreaView className="flex-1 bg-white">
            {/* Header */}
            <View className="flex-row items-center p-4 border-b border-gray-100 bg-white shadow-sm z-10">
                <TouchableOpacity onPress={() => navigation.goBack()} className="mr-3 p-1 rounded-full active:bg-gray-100">
                    <Ionicons name="arrow-back" size={24} color="#374151" />
                </TouchableOpacity>
                <Text className="text-xl font-bold text-gray-800 flex-1">{t('condolence')}</Text>
            </View>

            <View className="flex-1 bg-orange-50 items-center justify-center p-6">
                <View className="bg-white p-6 rounded-2xl shadow-lg w-full items-center border border-orange-100">
                    <View className="w-20 h-20 bg-orange-100 rounded-full items-center justify-center mb-6">
                        <Ionicons name="card-outline" size={40} color="#ea580c" />
                    </View>

                    <Text className="text-xl font-bold text-gray-800 text-center mb-3">
                        {t('generateCard')}
                    </Text>

                    <Text className="text-gray-600 text-center mb-8 px-4 leading-6">
                        Use our official website to create and download high-quality Shok Sandesh cards instantly.
                    </Text>

                    <TouchableOpacity
                        onPress={openWebsite}
                        className="bg-orange-600 w-full py-4 rounded-xl shadow-md active:bg-orange-700 flex-row justify-center items-center"
                    >
                        <Text className="text-white font-bold text-lg mr-2">Create Now</Text>
                        <Ionicons name="open-outline" size={20} color="white" />
                    </TouchableOpacity>

                    <TouchableOpacity
                        onPress={() => navigation.goBack()}
                        className="mt-4 py-2"
                    >
                        <Text className="text-gray-500 font-medium">Go Back</Text>
                    </TouchableOpacity>
                </View>
            </View>
        </SafeAreaView>
    );
};

export default ShokSanvedanaScreen;
