import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api, { API_BASE_URL } from '../../services/api';
import { Ionicons } from '@expo/vector-icons';
import { useLanguage } from '../../context/LanguageContext';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const TEMPLE_IMAGE_URL = `${BASE_URL_ROOT}/uploads/temple/`;

const TempleScreen = ({ navigation }: any) => {
    const [temples, setTemples] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const { t } = useLanguage();

    useEffect(() => {
        fetchTemples();
    }, []);

    const fetchTemples = async () => {
        try {
            const res = await api.get('/get_temples.php');
            if (res.data.status === 'success') {
                setTemples(res.data.data);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const renderItem = ({ item }: { item: any }) => (
        <View className="bg-white mb-5 rounded-xl border border-orange-100 shadow-sm overflow-hidden mx-4">
            {item.photo && (
                <Image
                    source={{ uri: `${TEMPLE_IMAGE_URL}${item.photo}` }}
                    className="w-full h-72 bg-gray-50"
                    resizeMode="contain"
                />
            )}
            <View className="p-4">
                <Text className="text-xl font-bold text-gray-900 mb-1">{item.mahant_name}</Text>
                <Text className="text-sm text-gray-600 mb-2">
                    <Ionicons name="call" size={14} color="gray" /> {item.mobile}
                </Text>

                <View className="space-y-1 mb-3">
                    <Text className="text-xs text-gray-500">{t('village')}: {item.village}</Text>
                    <Text className="text-xs text-gray-500">Taluka: {item.taluka}</Text>
                    <Text className="text-xs text-gray-500">District: {item.district}</Text>
                </View>

                {item.description && (
                    <Text className="text-gray-700 text-sm leading-5" numberOfLines={3}>
                        {item.description}
                    </Text>
                )}
            </View>
        </View>
    );

    if (loading) {
        return <View className="flex-1 justify-center items-center bg-white"><ActivityIndicator color="#ea580c" /></View>;
    }

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            <View className="flex-row items-center p-4 border-b border-gray-100">
                <TouchableOpacity onPress={() => navigation.goBack()} className="mr-3">
                    <Ionicons name="arrow-back" size={24} color="black" />
                </TouchableOpacity>
                <Text className="text-xl font-bold text-gray-800">{t('temples')}</Text>
            </View>

            <FlatList
                data={temples}
                renderItem={renderItem}
                keyExtractor={item => item.temple_id.toString()}
                contentContainerStyle={{ paddingVertical: 10 }}
                ListEmptyComponent={
                    <View className="items-center mt-20 p-4">
                        <Text className="text-gray-500 text-center text-lg">{t('noJobs') ?? "No temples found"}</Text>
                    </View>
                }
            />
        </SafeAreaView>
    );
};

export default TempleScreen;
