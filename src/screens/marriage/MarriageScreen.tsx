import React, { useEffect, useState, useCallback, useRef } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator, TextInput, Alert, ScrollView, LayoutAnimation, Platform, UIManager, Animated } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';

// Enable LayoutAnimation for Android
if (Platform.OS === 'android' && UIManager.setLayoutAnimationEnabledExperimental) {
    UIManager.setLayoutAnimationEnabledExperimental(true);
}

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;
const QUICK_TAGS = ['All', 'Delhi', 'Mumbai', 'B.Tech', 'MBA', 'Doctor', 'B.Sc', 'MCA'];

const MarriageScreen = ({ navigation }: any) => {
    const [profiles, setProfiles] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [myProfileId, setMyProfileId] = useState<number>(0);
    const [requestCount, setRequestCount] = useState<number>(0);

    // Filters
    const [search, setSearch] = useState('');
    const [filterVisible, setFilterVisible] = useState(false);
    const filterAnim = useRef(new Animated.Value(0)).current;

    useEffect(() => {
        if (filterVisible) {
            filterAnim.setValue(0);
            Animated.spring(filterAnim, {
                toValue: 1,
                tension: 70,
                friction: 10,
                useNativeDriver: true,
            }).start();
        }
    }, [filterVisible]);

    const toggleFilter = () => {
        if (filterVisible) {
            Animated.timing(filterAnim, {
                toValue: 0,
                duration: 180,
                useNativeDriver: true,
            }).start(() => {
                setFilterVisible(false);
            });
        } else {
            setFilterVisible(true);
        }
    };

    const [gender, setGender] = useState('');
    const [ageGroup, setAgeGroup] = useState('');
    const [city, setCity] = useState('');
    const [education, setEducation] = useState('');
    const [minAge, setMinAge] = useState('');
    const [maxAge, setMaxAge] = useState('');

    // Pagination
    const [page, setPage] = useState(0);
    const [hasMore, setHasMore] = useState(true);
    const [isLoadMoreLoading, setIsLoadMoreLoading] = useState(false);

    const [userId, setUserId] = useState<string | null>(null);

    // Premium states
    const [activeTag, setActiveTag] = useState('All');
    const shimmerAnim = useRef(new Animated.Value(0.4)).current;

    useEffect(() => {
        if (loading) {
            Animated.loop(
                Animated.sequence([
                    Animated.timing(shimmerAnim, {
                        toValue: 1.0,
                        duration: 850,
                        useNativeDriver: true
                    }),
                    Animated.timing(shimmerAnim, {
                        toValue: 0.4,
                        duration: 850,
                        useNativeDriver: true
                    })
                ])
            ).start();
        } else {
            shimmerAnim.setValue(0.4);
        }
    }, [loading]);

    useEffect(() => {
        loadUser();
    }, []);

    useEffect(() => {
        if (userId && profiles.length === 0) {
            fetchProfiles();
        }
    }, [userId]);

    useEffect(() => {
        const unsubscribe = navigation.addListener('focus', () => {
            if (userId && profiles.length === 0) {
                fetchProfiles();
            }
        });
        return unsubscribe;
    }, [navigation, userId]);

    const loadUser = async () => {
        const u = await AsyncStorage.getItem('user');
        if (u) {
            setUserId(JSON.parse(u).id);
        }
    };

    const fetchProfiles = async (isMore = false, overrideFilters?: { gender?: string; city?: string; education?: string }) => {
        if (isMore && (isLoadMoreLoading || !hasMore)) return;

        if (isMore) setIsLoadMoreLoading(true);
        else setLoading(true);

        const activeGender = overrideFilters?.gender !== undefined ? overrideFilters.gender : gender;
        const activeCity = overrideFilters?.city !== undefined ? overrideFilters.city : city;
        const activeEducation = overrideFilters?.education !== undefined ? overrideFilters.education : education;

        try {
            const currentOffset = isMore ? (page + 1) * 20 : 0;
            const params = new URLSearchParams();
            params.append('user_id', userId || '0');
            params.append('limit', '20');
            params.append('offset', currentOffset.toString());

            if (search) params.append('city', search);
            if (activeGender) params.append('gender', activeGender);
            if (minAge && maxAge) params.append('age', `${minAge}-${maxAge}`);
            if (activeCity) params.append('city', activeCity);
            if (activeEducation) params.append('education', activeEducation);

            const res = await api.get(`/get_matrimony_profiles.php?${params.toString()}`);
            if (res.data.status === 'success') {
                const newData = res.data.data || [];

                if (newData.length < 20) {
                    setHasMore(false);
                } else {
                    setHasMore(true);
                }

                if (isMore) {
                    setProfiles(prev => [...prev, ...newData]);
                    setPage(prev => prev + 1);
                } else {
                    setProfiles(newData);
                    setPage(0);
                }

                setMyProfileId(res.data.my_profile_id);
                setRequestCount(res.data.request_count);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
            setIsLoadMoreLoading(false);
        }
    };

    const handleGenderSegmentPress = (selectedGender: string) => {
        setGender(selectedGender);
        fetchProfiles(false, { gender: selectedGender, city, education });
    };

    const handleTagPress = (tag: string) => {
        setActiveTag(tag);
        let nextCity = '';
        let nextEducation = '';
        
        if (tag === 'All') {
            nextCity = '';
            nextEducation = '';
            setCity('');
            setEducation('');
        } else if (['Delhi', 'Mumbai'].includes(tag)) {
            nextCity = tag;
            nextEducation = '';
            setCity(tag);
            setEducation('');
        } else {
            nextCity = '';
            nextEducation = tag;
            setCity('');
            setEducation(tag);
        }
        
        fetchProfiles(false, { gender, city: nextCity, education: nextEducation });
    };

    const renderSkeletonCard = () => {
        return (
            <Animated.View
                style={{
                    flex: 1,
                    backgroundColor: 'white',
                    margin: 6,
                    borderRadius: 20,
                    borderWidth: 1,
                    borderColor: '#f3f4f6',
                    overflow: 'hidden',
                    opacity: shimmerAnim
                }}
            >
                <View style={{ width: '100%', height: 160, backgroundColor: '#e5e7eb' }} />
                <View style={{ padding: 12 }}>
                    <View style={{ width: '70%', height: 14, backgroundColor: '#e5e7eb', borderRadius: 4, marginBottom: 8 }} />
                    <View style={{ width: '90%', height: 10, backgroundColor: '#e5e7eb', borderRadius: 4 }} />
                </View>
                <View style={{ width: '100%', height: 36, backgroundColor: '#f9fafb', borderTopWidth: 1, borderColor: '#f3f4f6' }} />
            </Animated.View>
        );
    };

    const handleSendRequest = async (receiverId: string) => {
        if (!userId) {
            Alert.alert("Notice", "Please login.");
            return;
        }
        if (!myProfileId) {
            Alert.alert("Profile Required", "Please create your marriage profile first.", [
                { text: "Cancel" },
                { text: "Create Now", onPress: () => navigation.navigate('CreateMarriageProfile') }
            ]);
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'send_request');
            formData.append('user_id', userId);
            formData.append('receiver_id', receiverId);

            // using api_connect.php for unified connection handling
            const res = await api.post('/api_connect.php', formData);
            if (res.data.status === 'success') {
                Alert.alert("Success", "Proposal Sent Successfully");
                fetchProfiles(); // Refresh status
            } else {
                Alert.alert("Notice", res.data.message || "Failed to send request");
            }
        } catch (error) {
            Alert.alert("Error", "Network error");
        }
    };

    const renderProfile = ({ item }: { item: any }) => {
        const isSender = item.is_sender;
        const status = item.proposal_status;

        // Generate a deterministic compatibility score based on name & ID
        const scoreHash = (item.full_name?.charCodeAt(0) || 0) + (parseInt(item.id) || 0);
        const compatibilityScore = 78 + (scoreHash % 21); // Generates a percentage between 78% and 98%

        return (
            <TouchableOpacity
                activeOpacity={0.9}
                onPress={() => navigation.navigate('MarriageDetail', { profile: item })}
                style={{ 
                    flex: 1, 
                    backgroundColor: 'white', 
                    margin: 6, 
                    borderRadius: 20, 
                    borderWidth: 1, 
                    borderColor: '#f3f4f6', 
                    shadowColor: '#000', 
                    shadowOffset: { width: 0, height: 4 }, 
                    shadowOpacity: 0.03, 
                    shadowRadius: 10, 
                    elevation: 2, 
                    overflow: 'hidden' 
                }}
            >
                <View style={{ position: 'relative' }}>
                    <Image
                        source={{ uri: item.photo ? `${PHOTO_URL}${item.photo}` : 'https://via.placeholder.com/150' }}
                        style={{ width: '100%', height: 160, backgroundColor: '#f9fafb' }}
                        resizeMode="cover"
                    />
                    {/* Flame compatibility score badge */}
                    <View style={{ position: 'absolute', top: 10, left: 10, backgroundColor: 'rgba(255, 255, 255, 0.85)', paddingHorizontal: 6, paddingVertical: 3, borderRadius: 8, flexDirection: 'row', alignItems: 'center', gap: 2 }}>
                        <Text style={{ fontSize: 9, fontWeight: 'extrabold', color: '#ea580c' }}>🔥 {compatibilityScore}%</Text>
                    </View>
                    <View style={{ position: 'absolute', top: 10, right: 10, backgroundColor: 'rgba(255, 255, 255, 0.85)', paddingHorizontal: 8, paddingVertical: 3, borderRadius: 12 }}>
                        <Text style={{ color: '#ea580c', fontSize: 8, fontWeight: 'bold', textTransform: 'uppercase', letterSpacing: 0.5 }}>{item.status || 'UNMARRIED'}</Text>
                    </View>
                </View>

                <View style={{ padding: 12 }}>
                    <Text style={{ fontSize: 14, fontWeight: 'bold', color: '#1f2937' }} numberOfLines={1}>
                        {item.full_name}, {item.age}
                    </Text>
                    <Text style={{ fontSize: 11, fontWeight: '500', color: '#6b7280', marginTop: 4 }} numberOfLines={1}>
                        {item.city || 'Location'} • {item.education || 'Not specified'}
                    </Text>
                </View>

                {/* Card Footer Action Strip */}
                <View style={{ marginTop: 'auto' }}>
                    {(!status || status === 'rejected') && (
                        <View
                            style={{ width: '100%', backgroundColor: '#fff7ed', paddingVertical: 10, alignItems: 'center', justifyContent: 'center', borderTopWidth: 1, borderTopColor: '#ffedd5' }}
                        >
                            <Text style={{ color: '#ea580c', fontWeight: 'bold', fontSize: 11, letterSpacing: 0.3 }}>View Profile</Text>
                        </View>
                    )}

                    {status === 'pending' && (
                        <View
                            style={{ width: '100%', backgroundColor: '#f9fafb', paddingVertical: 10, alignItems: 'center', justifyContent: 'center', borderTopWidth: 1, borderTopColor: '#e5e7eb' }}
                        >
                            <Text style={{ color: '#9ca3af', fontWeight: 'bold', fontSize: 11, letterSpacing: 0.3 }}>Requested</Text>
                        </View>
                    )}

                    {(status === 'accepted' || status === 'friend') && (
                        <TouchableOpacity
                            style={{ width: '100%', backgroundColor: '#f0fdf4', paddingVertical: 10, alignItems: 'center', justifyContent: 'center', borderTopWidth: 1, borderTopColor: '#dcfce7' }}
                            onPress={() => navigation.navigate('Chat', { receiver: item, platform: 'marriage' })}
                            activeOpacity={0.8}
                        >
                            <Text style={{ color: '#16a34a', fontWeight: 'bold', fontSize: 11, letterSpacing: 0.3 }}>Message</Text>
                        </TouchableOpacity>
                    )}
                </View>
            </TouchableOpacity>
        );
    };

    return (
        <SafeAreaView className="flex-1 bg-gray-50">
            <View className="px-4 py-3 bg-white border-b border-gray-100 shadow-sm z-10">
                <View className="flex-row justify-between items-center">
                    <Text className="text-2xl font-extrabold text-gray-800 tracking-tight">Matrimony</Text>
                    <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                        <TouchableOpacity 
                            onPress={() => navigation.navigate('Requests')} 
                            style={{ width: 34, height: 34, borderRadius: 17, backgroundColor: '#fdf2f8', borderWidth: 1, borderColor: '#fce7f3', alignItems: 'center', justifyContent: 'center', marginRight: 6, position: 'relative' }}
                            activeOpacity={0.7}
                        >
                            <Ionicons name="heart" size={16} color="#db2777" />
                            {requestCount > 0 && (
                                <View style={{ position: 'absolute', top: -4, right: -4, backgroundColor: '#ef4444', minWidth: 14, height: 14, borderRadius: 7, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 2, borderWidth: 1, borderColor: 'white' }}>
                                    <Text style={{ color: 'white', fontSize: 7, fontWeight: 'extrabold' }}>{requestCount}</Text>
                                </View>
                            )}
                        </TouchableOpacity>

                        <TouchableOpacity 
                            onPress={() => navigation.navigate('Connected')} 
                            style={{ width: 34, height: 34, borderRadius: 17, backgroundColor: '#f0fdf4', borderWidth: 1, borderColor: '#dcfce7', alignItems: 'center', justifyContent: 'center', marginRight: 6 }}
                            activeOpacity={0.7}
                        >
                            <Ionicons name="people" size={16} color="#16a34a" />
                        </TouchableOpacity>

                        <TouchableOpacity 
                            onPress={toggleFilter} 
                            style={{ width: 34, height: 34, borderRadius: 17, backgroundColor: '#f9fafb', borderWidth: 1, borderColor: '#e5e7eb', alignItems: 'center', justifyContent: 'center', marginRight: 8 }}
                            activeOpacity={0.7}
                        >
                            <Ionicons name="options" size={16} color="#ea580c" />
                        </TouchableOpacity>

                        <TouchableOpacity 
                            onPress={() => navigation.navigate('CreateMarriageProfile', { profile: myProfileId ? { id: myProfileId } : null })} 
                            style={{ backgroundColor: '#fff7ed', borderWidth: 1, borderColor: '#ffedd5', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 20, flexDirection: 'row', alignItems: 'center', gap: 4 }}
                            activeOpacity={0.7}
                        >
                            <Ionicons name={myProfileId ? "create-outline" : "add-circle-outline"} size={14} color="#ea580c" />
                            <Text style={{ color: '#ea580c', fontSize: 11, fontWeight: 'bold' }}>{myProfileId ? 'Profile' : 'Create'}</Text>
                        </TouchableOpacity>
                    </View>
                </View>
            </View>

            {loading && myProfileId === 0 ? (
                <View style={{ flex: 1, backgroundColor: '#f9fafb', paddingTop: 16 }}>
                    <FlatList
                        data={[1, 2, 3, 4, 5, 6]}
                        renderItem={renderSkeletonCard}
                        keyExtractor={(item) => item.toString()}
                        numColumns={2}
                        contentContainerStyle={{ paddingHorizontal: 6, paddingVertical: 6 }}
                    />
                </View>
            ) : !myProfileId ? (
                // Gate: No profile created yet
                <View className="flex-1 items-center justify-center px-8 bg-gray-50">
                    <View className="bg-white rounded-3xl p-8 items-center shadow-md border border-gray-100 w-full">
                        <View className="bg-orange-100 p-5 rounded-full mb-5">
                            <Ionicons name="heart-circle-outline" size={52} color="#ea580c" />
                        </View>
                        <Text className="text-xl font-extrabold text-gray-800 mb-2 text-center">Welcome to Matrimony!</Text>
                        <Text className="text-gray-500 text-sm text-center leading-5 mb-6">
                            To browse profiles and send proposals, you first need to create your own marriage profile.
                        </Text>
                        <TouchableOpacity
                            onPress={() => navigation.navigate('CreateMarriageProfile', { profile: null })}
                            className="bg-orange-600 w-full py-3.5 rounded-2xl items-center shadow-sm mb-3"
                        >
                            <Text className="text-white font-extrabold text-base">Create My Profile</Text>
                        </TouchableOpacity>
                        <Text className="text-gray-400 text-xs text-center">
                            After creating your profile, you can view other profiles and send requests.
                        </Text>
                    </View>
                </View>
            ) : (
                <View style={{ flex: 1, position: 'relative' }}>
                    {/* Segment Switcher */}
                    <View style={{ flexDirection: 'row', backgroundColor: '#f3f4f6', borderRadius: 24, padding: 3, marginHorizontal: 16, marginTop: 12, marginBottom: 8 }}>
                        <TouchableOpacity
                            onPress={() => handleGenderSegmentPress('')}
                            style={{ flex: 1, paddingVertical: 8, alignItems: 'center', justifyContent: 'center', backgroundColor: gender === '' ? 'white' : 'transparent', borderRadius: 20, shadowColor: gender === '' ? '#000' : 'transparent', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.05, shadowRadius: 3, elevation: gender === '' ? 2 : 0 }}
                            activeOpacity={0.8}
                        >
                            <Text style={{ fontSize: 13, fontWeight: 'bold', color: gender === '' ? '#ea580c' : '#4b5563' }}>All Matches</Text>
                        </TouchableOpacity>
                        <TouchableOpacity
                            onPress={() => handleGenderSegmentPress('Male')}
                            style={{ flex: 1, paddingVertical: 8, alignItems: 'center', justifyContent: 'center', backgroundColor: gender === 'Male' ? 'white' : 'transparent', borderRadius: 20, shadowColor: gender === 'Male' ? '#000' : 'transparent', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.05, shadowRadius: 3, elevation: gender === 'Male' ? 2 : 0 }}
                            activeOpacity={0.8}
                        >
                            <Text style={{ fontSize: 13, fontWeight: 'bold', color: gender === 'Male' ? '#ea580c' : '#4b5563' }}>Grooms</Text>
                        </TouchableOpacity>
                        <TouchableOpacity
                            onPress={() => handleGenderSegmentPress('Female')}
                            style={{ flex: 1, paddingVertical: 8, alignItems: 'center', justifyContent: 'center', backgroundColor: gender === 'Female' ? 'white' : 'transparent', borderRadius: 20, shadowColor: gender === 'Female' ? '#000' : 'transparent', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.05, shadowRadius: 3, elevation: gender === 'Female' ? 2 : 0 }}
                            activeOpacity={0.8}
                        >
                            <Text style={{ fontSize: 13, fontWeight: 'bold', color: gender === 'Female' ? '#ea580c' : '#4b5563' }}>Brides</Text>
                        </TouchableOpacity>
                    </View>

                    {/* Quick Tags Filter Carousel */}
                    <View style={{ marginBottom: 6 }}>
                        <ScrollView
                            horizontal
                            showsHorizontalScrollIndicator={false}
                            contentContainerStyle={{ paddingHorizontal: 16, gap: 8, paddingBottom: 6 }}
                        >
                            {QUICK_TAGS.map((tag) => (
                                <TouchableOpacity
                                    key={tag}
                                    onPress={() => handleTagPress(tag)}
                                    style={{
                                        paddingHorizontal: 16,
                                        paddingVertical: 8,
                                        borderRadius: 20,
                                        backgroundColor: activeTag === tag ? '#ea580c' : 'white',
                                        borderWidth: 1,
                                        borderColor: activeTag === tag ? '#ea580c' : '#e5e7eb',
                                        shadowColor: '#000',
                                        shadowOffset: { width: 0, height: 1 },
                                        shadowOpacity: 0.03,
                                        shadowRadius: 2,
                                        elevation: 1
                                    }}
                                    activeOpacity={0.7}
                                >
                                    <Text style={{ fontSize: 12, fontWeight: 'bold', color: activeTag === tag ? 'white' : '#6b7280' }}>
                                        {tag}
                                    </Text>
                                </TouchableOpacity>
                            ))}
                        </ScrollView>
                    </View>

                    {loading ? (
                        <FlatList
                            data={[1, 2, 3, 4, 5, 6]}
                            renderItem={renderSkeletonCard}
                            keyExtractor={(item) => item.toString()}
                            numColumns={2}
                            contentContainerStyle={{ paddingHorizontal: 6, paddingVertical: 6 }}
                        />
                    ) : (
                        <FlatList
                            data={profiles}
                            renderItem={renderProfile}
                            keyExtractor={item => item.id?.toString()}
                            numColumns={2}
                            onEndReached={() => fetchProfiles(true)}
                            onEndReachedThreshold={0.5}
                            ListFooterComponent={
                                isLoadMoreLoading ? (
                                    <View className="py-4">
                                        <ActivityIndicator color="#ea580c" />
                                    </View>
                                ) : null
                            }
                            contentContainerStyle={{ paddingHorizontal: 6, paddingVertical: 10, paddingBottom: 80 }}
                            className="bg-gray-50"
                            ListEmptyComponent={
                                <View className="items-center justify-center py-20">
                                    <Image source={{ uri: 'https://cdn-icons-png.flaticon.com/512/7486/7486744.png' }} className="w-20 h-20 opacity-30 mb-4" />
                                    <Text className="text-gray-500 font-bold text-lg">No Profiles Found</Text>
                                    <Text className="text-gray-400 text-xs mt-1 text-center w-64">Try changing filters or check back later for new matches.</Text>
                                    <TouchableOpacity onPress={() => fetchProfiles()} className="mt-6 bg-orange-100 px-6 py-2 rounded-full">
                                        <Text className="text-orange-600 font-bold text-xs">Refresh</Text>
                                    </TouchableOpacity>
                                </View>
                            }
                        />
                    )}

                    {filterVisible && (
                        <>
                            {/* Backdrop overlay */}
                            <Animated.View 
                                style={{ 
                                    position: 'absolute', 
                                    top: 0, 
                                    left: 0, 
                                    right: 0, 
                                    bottom: 0, 
                                    backgroundColor: 'rgba(0,0,0,0.3)', 
                                    zIndex: 90,
                                    opacity: filterAnim
                                }}
                            >
                                <TouchableOpacity 
                                    activeOpacity={1} 
                                    onPress={toggleFilter}
                                    style={{ flex: 1 }}
                                />
                            </Animated.View>

                            {/* Floating Filter Panel */}
                            <Animated.View 
                                style={{ 
                                    position: 'absolute', 
                                    top: 8, 
                                    left: 12, 
                                    right: 12, 
                                    backgroundColor: 'white', 
                                    padding: 20, 
                                    borderRadius: 24, 
                                    borderWidth: 1, 
                                    borderColor: '#f3f4f6', 
                                    shadowColor: '#000', 
                                    shadowOffset: { width: 0, height: 10 }, 
                                    shadowOpacity: 0.15, 
                                    shadowRadius: 20, 
                                    elevation: 10, 
                                    zIndex: 100,
                                    opacity: filterAnim,
                                    transform: [
                                        { translateY: filterAnim.interpolate({ inputRange: [0, 1], outputRange: [-50, 0] }) },
                                        { scale: filterAnim.interpolate({ inputRange: [0, 1], outputRange: [0.95, 1] }) }
                                    ]
                                }}
                            >
                                <Text style={{ fontSize: 11, fontWeight: 'bold', color: '#9ca3af', textTransform: 'uppercase', marginBottom: 8, letterSpacing: 0.5 }}>Gender</Text>
                                <View style={{ flexDirection: 'row', gap: 12, marginBottom: 16 }}>
                                    <TouchableOpacity 
                                        onPress={() => setGender('Male')} 
                                        style={{ flex: 1, borderRadius: 24, borderWidth: 1, borderColor: gender === 'Male' ? '#ea580c' : '#e5e7eb', backgroundColor: gender === 'Male' ? '#ea580c' : '#f9fafb', paddingVertical: 12, alignItems: 'center' }}
                                        activeOpacity={0.8}
                                    >
                                        <Text style={{ fontSize: 13, fontWeight: 'bold', color: gender === 'Male' ? 'white' : '#4b5563' }}>Groom (Male)</Text>
                                    </TouchableOpacity>
                                    <TouchableOpacity 
                                        onPress={() => setGender('Female')} 
                                        style={{ flex: 1, borderRadius: 24, borderWidth: 1, borderColor: gender === 'Female' ? '#db2777' : '#e5e7eb', backgroundColor: gender === 'Female' ? '#db2777' : '#f9fafb', paddingVertical: 12, alignItems: 'center' }}
                                        activeOpacity={0.8}
                                    >
                                        <Text style={{ fontSize: 13, fontWeight: 'bold', color: gender === 'Female' ? 'white' : '#4b5563' }}>Bride (Female)</Text>
                                    </TouchableOpacity>
                                </View>

                                <Text style={{ fontSize: 11, fontWeight: 'bold', color: '#9ca3af', textTransform: 'uppercase', marginBottom: 8, letterSpacing: 0.5 }}>Age Range</Text>
                                <View style={{ flexDirection: 'row', gap: 12, marginBottom: 16 }}>
                                    <View style={{ flex: 1, backgroundColor: '#f9fafb', borderRadius: 24, borderWidth: 1, borderColor: '#e5e7eb', flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16 }}>
                                        <TextInput placeholder="Min Age" value={minAge} onChangeText={setMinAge} keyboardType="numeric" style={{ flex: 1, paddingVertical: 10, fontSize: 13, color: '#111827' }} placeholderTextColor="#9ca3af" />
                                    </View>
                                    <View style={{ flex: 1, backgroundColor: '#f9fafb', borderRadius: 24, borderWidth: 1, borderColor: '#e5e7eb', flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16 }}>
                                        <TextInput placeholder="Max Age" value={maxAge} onChangeText={setMaxAge} keyboardType="numeric" style={{ flex: 1, paddingVertical: 10, fontSize: 13, color: '#111827' }} placeholderTextColor="#9ca3af" />
                                    </View>
                                </View>

                                <Text style={{ fontSize: 11, fontWeight: 'bold', color: '#9ca3af', textTransform: 'uppercase', marginBottom: 8, letterSpacing: 0.5 }}>Location & Education</Text>
                                <View style={{ flexDirection: 'row', gap: 12, marginBottom: 20 }}>
                                    <View style={{ flex: 1, backgroundColor: '#f9fafb', borderRadius: 24, borderWidth: 1, borderColor: '#e5e7eb', flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16 }}>
                                        <Ionicons name="location-outline" size={16} color="#9ca3af" style={{ marginRight: 4 }} />
                                        <TextInput placeholder="City..." value={city} onChangeText={setCity} style={{ flex: 1, paddingVertical: 10, fontSize: 13, color: '#111827' }} placeholderTextColor="#9ca3af" />
                                    </View>
                                    <View style={{ flex: 1, backgroundColor: '#f9fafb', borderRadius: 24, borderWidth: 1, borderColor: '#e5e7eb', flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16 }}>
                                        <Ionicons name="school-outline" size={16} color="#9ca3af" style={{ marginRight: 4 }} />
                                        <TextInput placeholder="Degree..." value={education} onChangeText={setEducation} style={{ flex: 1, paddingVertical: 10, fontSize: 13, color: '#111827' }} placeholderTextColor="#9ca3af" />
                                    </View>
                                </View>

                                <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingTop: 12, borderTopWidth: 1, borderColor: '#f3f4f6' }}>
                                    <TouchableOpacity onPress={() => { setGender(''); setCity(''); setEducation(''); setMinAge(''); setMaxAge(''); }} style={{ paddingHorizontal: 12, paddingVertical: 6 }} activeOpacity={0.7}>
                                        <Text style={{ color: '#ea580c', fontSize: 13, fontWeight: 'bold' }}>Reset All</Text>
                                    </TouchableOpacity>
                                    <TouchableOpacity onPress={() => { toggleFilter(); fetchProfiles(); }} style={{ backgroundColor: '#ea580c', paddingHorizontal: 24, paddingVertical: 10, borderRadius: 24, shadowColor: '#ea580c', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.15, shadowRadius: 6, elevation: 3 }} activeOpacity={0.8}>
                                        <Text style={{ color: 'white', fontSize: 13, fontWeight: 'bold' }}>Apply Filters</Text>
                                    </TouchableOpacity>
                                </View>
                            </Animated.View>
                        </>
                    )}
                </View>
            )}
        </SafeAreaView>
    );
};

export default MarriageScreen;
