import React from 'react';
import { View, Text, Image, ScrollView, TouchableOpacity, Dimensions, Share, Alert, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import api, { API_BASE_URL, WEBSITE_URL } from '../../services/api';

const { width } = Dimensions.get('window');
const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const NEWS_URL = `${BASE_URL_ROOT}/uploads/news/`;

const NewsDetailScreen = ({ route, navigation }: any) => {
    // Params passed from navigation (news object from API or newsId from linking)
    const { news, newsId } = route.params || {};
    const [currentNews, setCurrentNews] = React.useState<any>(news || null);
    const [loading, setLoading] = React.useState(!news && !!newsId);

    React.useEffect(() => {
        if (!currentNews && newsId) {
            fetchSingleNews(newsId);
        }
    }, [newsId]);

    const fetchSingleNews = async (id: string) => {
        try {
            setLoading(true);
            const res = await api.get(`get_news.php?id=${id}`);
            // Check if response is array or success object
            const data = Array.isArray(res.data) ? res.data : (res.data.data || []);
            if (data.length > 0) {
                setCurrentNews(data[0]);
            } else {
                Alert.alert("Error", "News not found");
                navigation.goBack();
            }
        } catch (e) {
            Alert.alert("Error", "Failed to load news");
            navigation.goBack();
        } finally {
            setLoading(false);
        }
    };

    const handleShare = async () => {
        try {
            if (!currentNews) return;
            const newsUrl = `${WEBSITE_URL}/view_news.php?id=${currentNews.id}`;
            const message = `${currentNews.title}\n\n${currentNews.description?.substring(0, 100)}...\n\nOpen in App: ${newsUrl}`;
            await Share.share({ message, url: newsUrl });
        } catch (error: any) {
            // console.error(error);
        }
    };

    if (loading) return <View className="flex-1 bg-white items-center justify-center"><ActivityIndicator size="large" color="#ea580c" /></View>;
    if (!currentNews) return <View className="flex-1 bg-white items-center justify-center"><Text>News not found</Text></View>;

    const images = currentNews.images || (currentNews.image ? currentNews.image.split(',') : []);

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            {/* Header */}
            <View className="flex-row items-center px-4 py-3 border-b border-gray-100">
                <TouchableOpacity onPress={() => navigation.goBack()} className="mr-3">
                    <Ionicons name="arrow-back" size={24} color="black" />
                </TouchableOpacity>
                <Text className="font-bold text-lg text-gray-800 flex-1 truncate" numberOfLines={1}>
                    News Detail
                </Text>
                <TouchableOpacity onPress={handleShare}>
                    <Ionicons name="share-social-outline" size={24} color="black" />
                </TouchableOpacity>
            </View>

            <ScrollView contentContainerStyle={{ paddingBottom: 40 }}>
                <View className="p-5">
                    <Text className="text-2xl font-bold text-gray-900 mb-2 leading-8">{news.title}</Text>
                    <Text className="text-orange-600 font-medium mb-4">{news.created_at}</Text>
                </View>

                {/* Horizontal Image Slider */}
                {images.length > 0 ? (
                    <View className="mb-4 h-[350px] bg-gray-50">
                        <ScrollView
                            horizontal
                            pagingEnabled
                            showsHorizontalScrollIndicator={false}
                            style={{ height: 350 }}
                        >
                            {images.map((img: string, index: number) => (
                                <Image
                                    key={index}
                                    source={{ uri: `${NEWS_URL}${img}` }}
                                    style={{ width: width, height: 350 }}
                                    className="bg-gray-100"
                                    resizeMode="contain"
                                />
                            ))}
                        </ScrollView>
                        {images.length > 1 && (
                            <View className="absolute bottom-3 right-4 bg-black/60 px-3 py-1 rounded-full">
                                <Text className="text-white text-xs font-bold">Swipe ↔</Text>
                            </View>
                        )}
                    </View>
                ) : null}

                <View className="px-5">
                    <Text className="text-gray-700 text-base leading-7">
                        {news.description}
                    </Text>
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

export default NewsDetailScreen;
