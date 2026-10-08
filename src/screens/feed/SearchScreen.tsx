import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, TextInput, FlatList, TouchableOpacity, ActivityIndicator, Image, ScrollView, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import PostCard from '../../components/PostCard';
import { useLanguage } from '../../context/LanguageContext';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const SearchScreen = ({ navigation }: any) => {
    const { t } = useLanguage();
    const [query, setQuery] = useState('');
    const [activeTab, setActiveTab] = useState<'All' | 'People' | 'Posts'>('All');
    const [loading, setLoading] = useState(false);
    const [users, setUsers] = useState<any[]>([]);
    const [posts, setPosts] = useState<any[]>([]);
    const [currentUserId, setCurrentUserId] = useState('');

    useEffect(() => {
        const getUid = async () => {
            const uStr = await AsyncStorage.getItem('user');
            if (uStr) {
                const u = JSON.parse(uStr);
                setCurrentUserId(u.id);
            }
        };
        getUid();
    }, []);

    const performSearch = useCallback(async (text: string) => {
        if (!text.trim()) {
            setUsers([]);
            setPosts([]);
            return;
        }
        setLoading(true);
        try {
            const res = await api.get(`/api_search.php?query=${encodeURIComponent(text)}&user_id=${currentUserId}`);
            if (res.data.ok) {
                setUsers(res.data.users || []);
                setPosts(res.data.posts || []);
            }
        } catch (e) {
            console.error(e);
        } finally {
            setLoading(false);
        }
    }, [currentUserId]);

    useEffect(() => {
        const delaySearch = setTimeout(() => {
            performSearch(query);
        }, 500);
        return () => clearTimeout(delaySearch);
    }, [query, performSearch]);

    const renderUser = ({ item }: { item: any }) => (
        <TouchableOpacity
            className="flex-row items-center bg-white p-4 mb-2 mx-4 rounded-2xl border border-gray-100 shadow-sm"
            onPress={() => navigation.navigate('PublicProfile', { userId: item.id })}
        >
            <Image
                source={{ uri: item.profile_photo ? `${PHOTO_URL}${item.profile_photo}` : 'https://via.placeholder.com/100' }}
                className="w-14 h-14 rounded-full bg-gray-200"
            />
            <View className="ml-4 flex-1">
                <Text className="font-bold text-gray-900 text-base">{item.name}</Text>
                <View className="flex-row items-center mt-1">
                    <Ionicons name="location-outline" size={14} color="#6b7280" />
                    <Text className="text-gray-500 text-sm ml-1">{item.city || 'No City'}</Text>
                </View>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#e5e7eb" />
        </TouchableOpacity>
    );

    const renderPost = ({ item }: { item: any }) => (
        <View className="mb-4">
            <PostCard
                post={{
                    ...item,
                    avatar: item.profile_photo ? `${PHOTO_URL}${item.profile_photo}` : 'https://via.placeholder.com/100'
                }}
                currentUserId={currentUserId}
                onUserPress={() => navigation.navigate('PublicProfile', { userId: item.user_id })}
                onDeletePress={() => { }} // Search results usually don't have delete
            />
        </View>
    );

    const filteredData = () => {
        if (activeTab === 'People') return users;
        if (activeTab === 'Posts') return posts;

        // Tab 'All' -> Combined list
        const combined = [];
        if (users.length > 0) combined.push({ type: 'header', title: 'People' }, ...users.slice(0, 5));
        if (posts.length > 0) combined.push({ type: 'header', title: 'Posts' }, ...posts);
        return combined;
    };

    return (
        <SafeAreaView className="flex-1 bg-gray-50" edges={['top']}>
            {/* Header / Search Bar */}
            <View className="bg-white px-4 pt-2 pb-4 shadow-sm">
                <View className="flex-row items-center">
                    <TouchableOpacity onPress={() => navigation.goBack()} className="p-2 -ml-2">
                        <Ionicons name="arrow-back" size={24} color="#374151" />
                    </TouchableOpacity>
                    <View className="flex-1 bg-gray-100 flex-row items-center px-4 rounded-full border border-gray-200">
                        <Ionicons name="search" size={20} color="#9ca3af" />
                        <TextInput
                            placeholder="Search people, posts..."
                            className="flex-1 py-2.5 ml-2 text-gray-900"
                            value={query}
                            onChangeText={setQuery}
                            autoFocus
                        />
                        {query.length > 0 && (
                            <TouchableOpacity onPress={() => setQuery('')}>
                                <Ionicons name="close-circle" size={18} color="#9ca3af" />
                            </TouchableOpacity>
                        )}
                    </View>
                </View>
            </View>

            {/* Tabs */}
            <View className="flex-row bg-white border-b border-gray-100 px-4">
                {['All', 'People', 'Posts'].map((tab) => (
                    <TouchableOpacity
                        key={tab}
                        onPress={() => setActiveTab(tab as any)}
                        className={`py-3 px-4 mr-2 ${activeTab === tab ? 'border-b-2 border-orange-500' : ''}`}
                    >
                        <Text className={`font-bold ${activeTab === tab ? 'text-orange-600' : 'text-gray-500'}`}>{tab}</Text>
                    </TouchableOpacity>
                ))}
            </View>

            {loading ? (
                <View className="flex-1 justify-center items-center">
                    <ActivityIndicator size="large" color="#ea580c" />
                </View>
            ) : (
                <FlatList
                    data={filteredData()}
                    keyExtractor={(item, index) => item.type === 'header' ? `header-${item.title}` : (item.id?.toString() || index.toString())}
                    renderItem={({ item }) => {
                        if (item.type === 'header') {
                            return (
                                <View className="px-4 py-3 mt-2">
                                    <Text className="text-xs font-black text-gray-400 uppercase tracking-widest">{item.title}</Text>
                                </View>
                            );
                        }
                        return item.name ? renderUser({ item }) : renderPost({ item });
                    }}
                    contentContainerStyle={{ paddingBottom: 20 }}
                    ListEmptyComponent={
                        query.length > 0 ? (
                            <View className="flex-1 items-center justify-center pt-20">
                                <Ionicons name="search-outline" size={64} color="#e5e7eb" />
                                <Text className="text-gray-500 mt-4 font-medium">No results found for "{query}"</Text>
                            </View>
                        ) : (
                            <View className="flex-1 items-center justify-center pt-20">
                                <Ionicons name="people-outline" size={64} color="#e5e7eb" />
                                <Text className="text-gray-400 mt-4 text-center px-10">Search for friends or community posts to stay connected</Text>
                            </View>
                        )
                    }
                />
            )}
        </SafeAreaView>
    );
};

export default SearchScreen;
