import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api, { API_BASE_URL } from '../../services/api';
import { Ionicons } from '@expo/vector-icons';
import { useLanguage } from '../../context/LanguageContext';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const BRANCH_IMAGE_URL = `${BASE_URL_ROOT}/uploads/branches/`;

const BranchScreen = ({ navigation }: any) => {
    const [branches, setBranches] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const { t } = useLanguage();

    useEffect(() => {
        fetchBranches();
    }, []);

    const fetchBranches = async () => {
        try {
            const res = await api.get('/get_branches.php');
            if (res.data.status === 'success') {
                setBranches(res.data.data);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const renderItem = ({ item }: { item: any }) => <BranchCard item={item} t={t} />;

    if (loading) {
        return <View className="flex-1 justify-center items-center bg-white"><ActivityIndicator color="#ea580c" /></View>;
    }

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            <View className="flex-row items-center p-4 border-b border-gray-100">
                <TouchableOpacity onPress={() => navigation.goBack()} className="mr-3">
                    <Ionicons name="arrow-back" size={24} color="black" />
                </TouchableOpacity>
                <Text className="text-xl font-bold text-gray-800">{t('branches')}</Text>
            </View>

            <FlatList
                data={branches}
                renderItem={renderItem}
                keyExtractor={item => item.id.toString()}
                contentContainerStyle={{ paddingVertical: 10 }}
                ListEmptyComponent={
                    <View className="items-center mt-20 p-4">
                        <Text className="text-gray-500 text-center text-lg">No branches found.</Text>
                    </View>
                }
            />
        </SafeAreaView>
    );
};

const BranchCard = ({ item, t }: { item: any, t: any }) => {
    const [expanded, setExpanded] = useState(false);
    const hasLongText = item.details && item.details.length > 100;

    return (
        <View className="bg-white mb-5 rounded-xl border border-orange-100 shadow-sm overflow-hidden mx-4">
            {item.photo && (
                <Image
                    source={{ uri: `${BRANCH_IMAGE_URL}${item.photo}` }}
                    className="w-full h-72 bg-gray-50"
                    resizeMode="contain"
                />
            )}
            <View className="p-4">
                <Text className="text-xl font-bold text-gray-900 mb-1">{item.branch_name}</Text>
                <Text className="text-md text-orange-600 font-semibold mb-2">{item.mahant_name}</Text>
                <Text className="text-sm text-gray-600 mb-2">
                    <Ionicons name="call" size={14} color="gray" /> {item.mahant_mobile}
                </Text>
                <Text className="text-xs text-gray-500 mb-2">{t('location')}: {item.branch_village}</Text>

                {item.details && (
                    <View>
                        <Text
                            className="text-gray-700 text-sm leading-5"
                            numberOfLines={expanded ? undefined : 3}
                        >
                            {item.details}
                        </Text>
                        {hasLongText && (
                            <TouchableOpacity onPress={() => setExpanded(!expanded)} className="mt-1">
                                <Text className="text-orange-600 font-bold text-xs">
                                    {expanded ? t('seeLess') : t('seeMore')}
                                </Text>
                            </TouchableOpacity>
                        )}
                    </View>
                )}
            </View>
        </View>
    );
};

export default BranchScreen;
