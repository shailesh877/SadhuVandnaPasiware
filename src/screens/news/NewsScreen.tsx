import React, { useEffect, useState, useRef } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator, ScrollView, Dimensions, Share } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import api, { API_BASE_URL, WEBSITE_URL } from '../../services/api';

const { width } = Dimensions.get('window');
const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const NEWS_URL = `${BASE_URL_ROOT}/uploads/news/`;

const NewsScreen = ({ navigation }: any) => {
    const [news, setNews] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const isMounted = useRef(true);

    useEffect(() => {
        isMounted.current = true;
        fetchNews();
        return () => { isMounted.current = false; };
    }, []);

    const fetchNews = async () => {
        try {
            const res = await api.get('/get_news.php');
            if (isMounted.current && res.data.status === 'success') {
                setNews(res.data.data);
            }
        } catch (error) {
            console.error("Failed to fetch news", error);
        } finally {
            if (isMounted.current) setLoading(false);
        }
    };

    const renderNewsItem = ({ item }: { item: any }) => {
        const images = item.images || [];
        const hasMultiple = images.length > 1;

        const handleShare = async () => {
            try {
                const newsUrl = `${WEBSITE_URL}/view_news.php?id=${item.id}`;
                const message = `${item.title}\n\n${item.description?.substring(0, 100)}...\n\nRead Full News: ${newsUrl}\n\nDownload App: ${WEBSITE_URL}`;
                await Share.share({ message, url: newsUrl });
            } catch (error: any) {
                // console.error(error);
            }
        };

        return (
            <View className="bg-white rounded-2xl shadow-sm border border-gray-100 mb-5 overflow-hidden">
                <View className="p-3">
                    {/* Header */}
                    <Text className="text-lg font-bold text-gray-900 leading-6 mb-1">{item.title}</Text>
                    <Text className="text-xs text-orange-500 mb-3">{item.created_at}</Text>

                    {/* Content Layout */}
                    <View className="flex-row">
                        <View className="flex-1 pr-2">
                            <Text className="text-gray-600 text-sm leading-5" numberOfLines={5}>
                                {item.description}
                            </Text>
                            <View className="flex-row mt-3 gap-4">
                                <TouchableOpacity onPress={() => navigation.navigate('NewsDetail', { news: item })}>
                                    <Text className="text-orange-600 font-bold text-xs uppercase tracking-wide">Read More</Text>
                                </TouchableOpacity>
                                <TouchableOpacity onPress={handleShare}>
                                    <View className="flex-row items-center">
                                        <Ionicons name="share-social-outline" size={14} color="gray" />
                                        <Text className="text-gray-500 font-bold text-xs ml-1">Share</Text>
                                    </View>
                                </TouchableOpacity>
                            </View>
                        </View>

                        {/* Images Side */}
                        {images.length > 0 && (
                            <View className="w-28 flex-col gap-1">
                                <Image
                                    source={{ uri: `${NEWS_URL}${images[0]}` }}
                                    className={`w-full bg-gray-100 rounded-lg ${hasMultiple ? 'h-20' : 'h-28'}`}
                                    resizeMode="cover"
                                />
                                {hasMultiple && (
                                    <View className="flex-1 relative">
                                        <Image
                                            source={{ uri: `${NEWS_URL}${images[1]}` }}
                                            className="w-full h-10 rounded-lg bg-gray-100 opacity-80"
                                            resizeMode="cover"
                                        />
                                        {images.length > 2 && (
                                            <View className="absolute inset-0 bg-black/40 rounded-lg items-center justify-center">
                                                <Text className="text-white font-bold text-xs">+{images.length - 2}</Text>
                                            </View>
                                        )}
                                    </View>
                                )}
                            </View>
                        )}
                    </View>
                </View>
            </View>
        );
    };

    if (loading) {
        return (
            <View className="flex-1 justify-center items-center bg-white">
                <ActivityIndicator size="large" color="#ea580c" />
            </View>
        );
    }

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            <View className="px-4 py-3 border-b border-gray-100 bg-white z-10">
                <Text className="text-xl font-bold text-gray-800">News & Updates</Text>
            </View>

            <FlatList
                data={news}
                keyExtractor={item => item.id.toString()}
                contentContainerStyle={{ padding: 16 }}
                renderItem={renderNewsItem}
                ListEmptyComponent={<Text className="text-center text-gray-500 mt-10">No news available</Text>}
            />
        </SafeAreaView>
    );
};

export default NewsScreen;
