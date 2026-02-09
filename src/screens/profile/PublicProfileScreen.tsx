import React, { useEffect, useState } from 'react';
import { View, Text, Image, ScrollView, ActivityIndicator, TouchableOpacity, Linking, Dimensions, Alert, Modal, TextInput, FlatList, RefreshControl } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import api, { API_BASE_URL, WEBSITE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import PostImage from '../../components/PostImage';
import PostCard from '../../components/PostCard'; // IMPORTED POSTCARD
import * as ImagePicker from 'expo-image-picker';

// Fix: Point to Root uploads, not Api/uploads
const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;
// Matched with FamilyScreen.tsx pattern which seems to be the working one
const FAMILY_PHOTO_URL = `${BASE_URL_ROOT}/uploads/family/`;
const POST_IMAGE_URL = `${BASE_URL_ROOT}/uploads/posts/`;

const { width } = Dimensions.get('window');

const PublicProfileScreen = ({ route, navigation }: any) => {
    const { userId } = route.params;
    const [profile, setProfile] = useState<any>(null);
    const [posts, setPosts] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);

    const [currentUserId, setCurrentUserId] = useState<string | null>(null);

    // Family Add State
    const [modalVisible, setModalVisible] = useState(false);
    const [famName, setFamName] = useState('');
    const [famRelation, setFamRelation] = useState('');
    const [famGender, setFamGender] = useState('Male');
    const [famDob, setFamDob] = useState('');
    const [famOccupation, setFamOccupation] = useState('');
    const [famMaritalStatus, setFamMaritalStatus] = useState('Unmarried');
    const [famHeight, setFamHeight] = useState('');
    const [famWeight, setFamWeight] = useState('');
    const [famEducation, setFamEducation] = useState('');
    const [famIncome, setFamIncome] = useState('');
    const [famCaste, setFamCaste] = useState('');
    const [famKuldevi, setFamKuldevi] = useState('');

    const [famPhoto, setFamPhoto] = useState<string | null>(null);
    const [submittingFam, setSubmittingFam] = useState(false);

    // View Member Details State
    const [selectedMember, setSelectedMember] = useState<any>(null);
    const [viewModalVisible, setViewModalVisible] = useState(false);

    const [editFamId, setEditFamId] = useState<string | null>(null);

    useEffect(() => {
        // Load current user (viewer)
        const loadCurrentUser = async () => {
            const uStr = await AsyncStorage.getItem('user');
            if (uStr) {
                const u = JSON.parse(uStr);
                setCurrentUserId(u.id);
            }
        };
        loadCurrentUser();
    }, []);

    useEffect(() => {
        if (currentUserId !== null || userId) {
            fetchProfileData();
        }
    }, [userId, currentUserId]);

    // Refresh listener for when returning from edit screens
    useEffect(() => {
        const unsubscribe = navigation.addListener('focus', () => {
            if (currentUserId !== null || userId) {
                fetchProfileData();
            }
        });
        return unsubscribe;
    }, [navigation, currentUserId, userId]);


    const fetchProfileData = async () => {
        try {
            const uid = userId || currentUserId; // Fallback if userId param missing
            if (!uid) return;

            const [profileRes, postsRes] = await Promise.all([
                api.get(`/get_user_profile.php?user_id=${uid}`),
                api.get(`/get_posts.php?filter_user_id=${uid}&user_id=${currentUserId || 0}`)
            ]);

            if (profileRes.data.status === 'success') {
                setProfile(profileRes.data);
            }
            if (postsRes.data.status === 'success') {
                setPosts(postsRes.data.data);
            }
        } catch (error) {
            console.error(error);
            // Alert.alert("Error", "Failed to load profile");
        } finally {
            setLoading(false);
        }
    };

    const [refreshing, setRefreshing] = useState(false);

    const onRefresh = async () => {
        setRefreshing(true);
        await fetchProfileData();
        setRefreshing(false);
    };

    const handleDelete = async (postId: string) => {
        // This will be triggered by PostCard's internal menu if configured
        try {
            const formData = new FormData();
            formData.append('user_id', currentUserId || '');
            formData.append('post_id', postId);

            const res = await api.post('/delete_post.php', formData);
            if (res.data.status === 'success') {
                setPosts(prev => prev.filter(p => p.id !== postId));
                Alert.alert("Success", "Post deleted");
            } else {
                Alert.alert("Error", res.data.message || "Failed to delete");
            }
        } catch (error) {
            Alert.alert("Error", "Network request failed");
        }
    };

    const pickFamilyImage = async () => {
        const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ['images'],
            allowsEditing: true,
            aspect: [1, 1],
            quality: 0.7,
        });

        if (!result.canceled) {
            setFamPhoto(result.assets[0].uri);
        }
    };

    const handleEditMember = (member: any) => {
        setEditFamId(member.id);
        setFamName(member.name);
        setFamRelation(member.relation);
        setFamGender(member.gender || 'Male');
        setFamDob(member.dob || '');
        setFamOccupation(member.occupation || '');
        setFamMaritalStatus(member.marital_status || 'Unmarried');
        setFamHeight(member.height || '');
        setFamWeight(member.weight || '');
        setFamEducation(member.education || '');
        setFamIncome(member.income || '');
        setFamCaste(member.caste || '');
        setFamKuldevi(member.kuldevi || '');
        setFamPhoto(member.photo ? `${FAMILY_PHOTO_URL}${member.photo}` : null);
        setModalVisible(true);
        setViewModalVisible(false); // Close detail modal if open
    };

    const resetFamForm = () => {
        setEditFamId(null);
        setFamName('');
        setFamRelation('');
        setFamGender('Male');
        setFamOccupation('');
        setFamDob('');
        setFamMaritalStatus('Unmarried');
        setFamHeight('');
        setFamWeight('');
        setFamEducation('');
        setFamIncome('');
        setFamCaste('');
        setFamKuldevi('');
        setFamPhoto(null);
    };

    const handleAddMember = async () => {
        if (!famName || !famRelation || !currentUserId) {
            Alert.alert("Error", "Name and Relation are required");
            return;
        }

        setSubmittingFam(true);
        try {
            const formData = new FormData();
            formData.append('action', editFamId ? 'update' : 'add');
            if (editFamId) formData.append('id', editFamId);
            formData.append('user_id', currentUserId);
            formData.append('name', famName);
            formData.append('relation', famRelation);
            formData.append('gender', famGender);
            formData.append('dob', famDob);
            formData.append('occupation', famOccupation);
            formData.append('marital_status', famMaritalStatus);
            formData.append('height', famHeight);
            formData.append('weight', famWeight);
            formData.append('education', famEducation);
            formData.append('income', famIncome);
            formData.append('caste', famCaste);
            formData.append('kuldevi', famKuldevi);

            if (famPhoto && !famPhoto.startsWith('http')) {
                const filename = famPhoto.split('/').pop();
                const match = /\.(\w+)$/.exec(filename || '');
                const type = match ? `image/${match[1]}` : `image/jpeg`;
                formData.append('photo', { uri: famPhoto, name: filename, type } as any);
            }

            const res = await api.post('/manage_family.php', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (res.data.status === 'success') {
                Alert.alert("Success", editFamId ? "Member Updated" : "Member Added");
                setModalVisible(false);
                resetFamForm();
                fetchProfileData();
            } else {
                Alert.alert("Error", res.data.message || "Failed to save member");
            }
        } catch (error) {
            console.error(error);
            Alert.alert("Error", "Network request failed");
        } finally {
            setSubmittingFam(false);
        }
    };

    // Delete member 
    const handleDeleteMember = async (memberId: string) => {
        Alert.alert("Confirm Delete", "Delete this family member?", [
            { text: "Cancel", style: "cancel" },
            {
                text: "Delete", style: "destructive", onPress: async () => {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('id', memberId);
                        await api.post('/manage_family.php', formData);
                        fetchProfileData();
                    } catch (error) { console.error(error); }
                }
            }
        ]);
    };


    const getImageUrl = (photo: string | undefined | null, baseUrl: string) => {
        if (!photo) return 'https://via.placeholder.com/100';
        if (photo.startsWith('http')) return photo;
        return `${baseUrl}${photo}`;
    };


    if (loading) {
        return (
            <View className="flex-1 justify-center items-center bg-white">
                <ActivityIndicator size="large" color="#ea580c" />
            </View>
        );
    }

    if (!profile) {
        return (
            <View className="flex-1 justify-center items-center bg-white">
                <Text className="text-gray-500">User not found</Text>
            </View>
        );
    }

    const { user, family, marriage_profile } = profile;
    // Loose comparison for ID mismatch (number vs string)
    const isOwner = currentUserId == user.id;

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            <View className="flex-row items-center justify-between px-4 py-3 border-b border-gray-100">
                <View className="flex-row items-center">
                    <TouchableOpacity onPress={() => navigation.goBack()} className="mr-3">
                        <Ionicons name="arrow-back" size={24} color="black" />
                    </TouchableOpacity>
                    <Text className="text-lg font-bold text-gray-800">{user.name}</Text>
                </View>

            </View>

            <ScrollView
                className="flex-1 bg-gray-50"
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
            >
                {/* Cover & Profile Photo */}
                <View className="mb-14">
                    <View className="h-40 bg-gray-300">
                        {user.cover_photo && (
                            <Image
                                source={{ uri: getImageUrl(user.cover_photo, PHOTO_URL) }}
                                className="w-full h-full"
                                resizeMode="cover"
                            />
                        )}
                    </View>
                    <View className="absolute -bottom-12 left-4">
                        <Image
                            source={{ uri: getImageUrl(user.profile_photo, PHOTO_URL) }}
                            className="w-24 h-24 rounded-full border-4 border-white bg-gray-200"
                        />
                    </View>
                </View>

                {/* User Info */}
                <View className="px-4 mt-2 mb-6">
                    <Text className="text-2xl font-bold text-gray-900">{user.name}</Text>
                    {user.about ? <Text className="text-gray-600 italic mt-1">{user.about}</Text> : null}
                    {user.city ? (
                        <View className="flex-row items-center mt-2">
                            <Ionicons name="location-outline" size={16} color="gray" />
                            <Text className="text-gray-500 ml-1">{user.city}</Text>
                        </View>
                    ) : null}

                    {isOwner && (
                        <TouchableOpacity
                            className="mt-4 bg-gray-100 py-2 rounded-lg items-center border border-gray-200"
                            onPress={() => navigation.navigate('EditProfile')}
                        >
                            <Text className="font-bold text-gray-800">Edit Profile</Text>
                        </TouchableOpacity>
                    )}

                    {/* Detailed Info */}
                    <View className="mt-6 bg-white p-4 rounded-xl border border-gray-100 shadow-sm space-y-3">
                        <Text className="font-bold text-gray-900 border-b border-gray-100 pb-2 mb-1">Personal Details</Text>

                        <View className="flex-row flex-wrap gap-4">
                            {user.education ? (
                                <View className="w-[45%]">
                                    <Text className="text-xs text-gray-500 uppercase">Education</Text>
                                    <Text className="text-sm font-medium text-gray-800">{user.education}</Text>
                                </View>
                            ) : null}

                            {user.occupation ? (
                                <View className="w-[45%]">
                                    <Text className="text-xs text-gray-500 uppercase">Occupation</Text>
                                    <Text className="text-sm font-medium text-gray-800">{user.occupation}</Text>
                                </View>
                            ) : null}

                            {user.maritial_status ? (
                                <View className="w-[45%]">
                                    <Text className="text-xs text-gray-500 uppercase">Marital Status</Text>
                                    <Text className="text-sm font-medium text-gray-800">{user.maritial_status}</Text>
                                </View>
                            ) : null}

                            {user.hobbi ? (
                                <View className="w-[45%]">
                                    <Text className="text-xs text-gray-500 uppercase">Hobbies</Text>
                                    <Text className="text-sm font-medium text-gray-800">{user.hobbi}</Text>
                                </View>
                            ) : null}
                            {user.cast ? (
                                <View className="w-[45%]">
                                    <Text className="text-xs text-gray-500 uppercase">Caste</Text>
                                    <Text className="text-sm font-medium text-gray-800">{user.cast}</Text>
                                </View>
                            ) : null}
                        </View>
                    </View>
                </View>

                {/* Family & Marriage Section */}
                <View className="px-4 mb-6">
                    <View className="bg-white p-4 rounded-xl border border-orange-100 shadow-sm">
                        <Text className="text-lg font-bold text-orange-700 mb-4 flex-row items-center">
                            <Ionicons name="people" size={20} /> Family & Marriage Info
                        </Text>

                        {/* Family */}
                        <View className="flex-row justify-between items-center mb-2">
                            <Text className="font-bold text-gray-800">Family Members</Text>
                            {isOwner && (
                                <TouchableOpacity onPress={() => { resetFamForm(); setModalVisible(true); }}>
                                    <Text className="text-orange-600 font-bold text-xs">+ Add Member</Text>
                                </TouchableOpacity>
                            )}
                        </View>

                        {family && family.length > 0 ? (
                            <ScrollView horizontal showsHorizontalScrollIndicator={false} className="mb-4">
                                {family.map((member: any, index: number) => (
                                    <TouchableOpacity
                                        key={index}
                                        className="mr-4 items-center w-20 relative"
                                        onPress={() => {
                                            setSelectedMember(member);
                                            setViewModalVisible(true);
                                        }}
                                    >
                                        <Image
                                            source={{ uri: member.photo ? `${FAMILY_PHOTO_URL}${member.photo}` : 'https://via.placeholder.com/100' }}
                                            className="w-14 h-14 rounded-full bg-gray-200 mb-1"
                                        />
                                        <Text className="text-xs text-center font-medium" numberOfLines={1}>{member.name}</Text>
                                        <Text className="text-[10px] text-gray-500 text-center">{member.relation}</Text>
                                        {isOwner && (
                                            <>
                                                <TouchableOpacity
                                                    className="absolute -top-1 -right-1 bg-red-100 rounded-full p-1 z-10"
                                                    onPress={() => handleDeleteMember(member.id)}
                                                >
                                                    <Ionicons name="close" size={10} color="red" />
                                                </TouchableOpacity>
                                                <TouchableOpacity
                                                    className="absolute -bottom-0 -right-1 bg-blue-100 rounded-full p-1 z-10"
                                                    onPress={() => handleEditMember(member)}
                                                >
                                                    <Ionicons name="pencil" size={10} color="blue" />
                                                </TouchableOpacity>
                                            </>
                                        )}
                                    </TouchableOpacity>
                                ))}
                            </ScrollView>
                        ) : (
                            <Text className="text-gray-400 text-sm mb-4">No family members added.</Text>
                        )}

                        {/* Marriage Profile */}
                        {marriage_profile ? (
                            <View className="bg-orange-50 border border-orange-200 rounded-lg p-3 mt-2 relative overflow-hidden">
                                <TouchableOpacity
                                    className="flex-row items-center"
                                    onPress={() => navigation.navigate('MarriageDetail', { profile: marriage_profile })}
                                >
                                    <View className="w-12 h-12 rounded-full bg-orange-200 items-center justify-center mr-3">
                                        <Ionicons name="heart" size={24} color="#ea580c" />
                                    </View>
                                    <View className="flex-1">
                                        <Text className="font-bold text-orange-800">Marriage Profile</Text>
                                        <Text className="text-xs text-gray-600">{marriage_profile.city}, {marriage_profile.caste}</Text>
                                    </View>
                                    <View>
                                        <Text className="text-xs text-orange-600 font-bold mb-1">VIEW</Text>
                                    </View>
                                </TouchableOpacity>
                                {isOwner && (
                                    <View className="flex-row justify-end mt-2 pt-2 border-t border-orange-200">
                                        <TouchableOpacity
                                            onPress={() => navigation.navigate('CreateMarriageProfile', { profile: marriage_profile })}
                                            className="bg-orange-600 px-3 py-1 rounded-full"
                                        >
                                            <Text className="text-white text-xs font-bold">Edit Profile</Text>
                                        </TouchableOpacity>
                                    </View>
                                )}
                            </View>
                        ) : isOwner ? (
                            <TouchableOpacity
                                className="bg-orange-50 border border-dashed border-orange-300 rounded-lg p-3 flex-row items-center mt-2 justify-center"
                                onPress={() => navigation.navigate('CreateMarriageProfile')}
                            >
                                <Ionicons name="add-circle-outline" size={24} color="#ea580c" />
                                <Text className="ml-2 font-bold text-orange-700">Create Matrimony Profile</Text>
                            </TouchableOpacity>
                        ) : null}
                    </View>
                </View>

                {/* Posts Section with PostCard */}
                <View>
                    <View className="px-4">
                        <Text className="font-bold text-gray-800 mb-2 ml-1">Posts</Text>
                    </View>
                    {posts.map((item, index) => (
                        <PostCard
                            key={item.id || index}
                            post={{
                                id: item.id,
                                user: {
                                    id: item.user_id,
                                    name: user.name, // Use profile name as it is the profile owner's post
                                    avatar: getImageUrl(user.profile_photo, PHOTO_URL),
                                },
                                content: item.description,
                                media: item.media,
                                image: item.image,
                                likes: parseInt(item.likes) || 0,
                                comments: item.comments?.length || 0,
                                timeAgo: item.date,
                                isLiked: item.user_liked
                            }}
                            currentUserId={currentUserId || ''}
                            onDeletePress={() => handleDelete(item.id)}
                        // No custom user press needed, we are already on the user's profile
                        />
                    ))}
                    {posts.length === 0 && (
                        <Text className="text-center text-gray-500 py-10">No posts shared yet.</Text>
                    )}
                </View>


                <View className="h-10" />
            </ScrollView>

            {/* Add Family Member Modal */}
            <Modal animationType="slide" visible={modalVisible} onRequestClose={() => setModalVisible(false)} presentationStyle="pageSheet">
                <View className="flex-1 bg-white">
                    <View className="flex-row items-center justify-between p-4 border-b border-gray-100">
                        <Text className="text-xl font-bold text-gray-800">{editFamId ? 'Edit Family Member' : 'Add Family Member'}</Text>
                        <TouchableOpacity onPress={() => setModalVisible(false)}>
                            <Text className="text-gray-500 text-lg">Cancel</Text>
                        </TouchableOpacity>
                    </View>
                    <ScrollView className="flex-1 p-5">

                        <View className="items-center mb-6">
                            <TouchableOpacity onPress={pickFamilyImage}>
                                {famPhoto ? (
                                    <Image source={{ uri: famPhoto }} className="w-24 h-24 rounded-full" />
                                ) : (
                                    <View className="w-24 h-24 rounded-full bg-gray-100 items-center justify-center border border-gray-300">
                                        <Ionicons name="camera" size={24} color="gray" />
                                        <Text className="text-xs text-gray-500 mt-1">Photo</Text>
                                    </View>
                                )}
                            </TouchableOpacity>
                        </View>

                        <View className="space-y-4 mb-20">
                            <View>
                                <Text className="mb-1 text-gray-600">Name *</Text>
                                <TextInput value={famName} onChangeText={setFamName} className="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-900" placeholderTextColor="#9ca3af" />
                            </View>
                            <View>
                                <Text className="mb-1 text-gray-600">Relation *</Text>
                                <TextInput value={famRelation} onChangeText={setFamRelation} className="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-900" placeholder="e.g. Brother" placeholderTextColor="#9ca3af" />
                            </View>
                            <View>
                                <Text className="mb-1 text-gray-600">Gender</Text>
                                <View className="flex-row gap-4">
                                    {['Male', 'Female', 'Other'].map(g => (
                                        <TouchableOpacity key={g} onPress={() => setFamGender(g)} className={`px-4 py-2 rounded-full border ${famGender === g ? 'bg-orange-50 border-orange-500' : 'border-gray-200'}`}>
                                            <Text className={famGender === g ? 'text-orange-600' : 'text-gray-600'}>{g}</Text>
                                        </TouchableOpacity>
                                    ))}
                                </View>
                            </View>

                            <View>
                                <Text className="mb-1 text-gray-600">Marital Status</Text>
                                <View className="flex-row gap-2 flex-wrap">
                                    {['Unmarried', 'Married', 'Divorced', 'Widow'].map(s => (
                                        <TouchableOpacity key={s} onPress={() => setFamMaritalStatus(s)} className={`px-3 py-1.5 rounded-full border ${famMaritalStatus === s ? 'bg-orange-50 border-orange-500' : 'border-gray-200'}`}>
                                            <Text className={`text-xs ${famMaritalStatus === s ? 'text-orange-600' : 'text-gray-600'}`}>{s}</Text>
                                        </TouchableOpacity>
                                    ))}
                                </View>
                            </View>

                            <View>
                                <Text className="mb-1 text-gray-600">Occupation</Text>
                                <TextInput value={famOccupation} onChangeText={setFamOccupation} className="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-900" placeholder="e.g. Engineer" placeholderTextColor="#9ca3af" />
                            </View>

                            <View>
                                <Text className="mb-1 text-gray-600">DOB (YYYY-MM-DD)</Text>
                                <TextInput value={famDob} onChangeText={setFamDob} className="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-900" placeholder="2000-01-01" placeholderTextColor="#9ca3af" />
                            </View>

                            <View className="flex-row gap-4">
                                <View className="flex-1">
                                    <Text className="mb-1 text-gray-600">Height</Text>
                                    <TextInput value={famHeight} onChangeText={setFamHeight} className="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-900" placeholder="e.g. 5.9" placeholderTextColor="#9ca3af" />
                                </View>
                                <View className="flex-1">
                                    <Text className="mb-1 text-gray-600">Weight</Text>
                                    <TextInput value={famWeight} onChangeText={setFamWeight} className="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-900" placeholder="e.g. 70kg" placeholderTextColor="#9ca3af" />
                                </View>
                            </View>

                            <View>
                                <Text className="mb-1 text-gray-600">Education</Text>
                                <TextInput value={famEducation} onChangeText={setFamEducation} className="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-900" placeholderTextColor="#9ca3af" />
                            </View>

                            <View>
                                <Text className="mb-1 text-gray-600">Monthly Income</Text>
                                <TextInput value={famIncome} onChangeText={setFamIncome} className="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-900" keyboardType="numeric" placeholderTextColor="#9ca3af" />
                            </View>

                            <View>
                                <Text className="mb-1 text-gray-600">Caste / Samaj</Text>
                                <TextInput value={famCaste} onChangeText={setFamCaste} className="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-900" placeholderTextColor="#9ca3af" />
                            </View>

                            <View>
                                <Text className="mb-1 text-gray-600">Kuldevi</Text>
                                <TextInput value={famKuldevi} onChangeText={setFamKuldevi} className="bg-gray-50 p-3 rounded-lg border border-gray-200 text-gray-900" placeholderTextColor="#9ca3af" />
                            </View>

                        </View>
                    </ScrollView>
                    <View className="p-4 border-t border-gray-100">
                        <TouchableOpacity onPress={handleAddMember} disabled={submittingFam} className="bg-orange-600 py-4 rounded-xl items-center">
                            {submittingFam ? <ActivityIndicator color="white" /> : <Text className="text-white font-bold text-lg">Save Member</Text>}
                        </TouchableOpacity>
                    </View>
                </View>
            </Modal>

            {/* View Member Details Modal */}
            <Modal animationType="fade" visible={viewModalVisible} onRequestClose={() => setViewModalVisible(false)} transparent={true}>
                <View className="flex-1 bg-black/50 justify-center items-center p-4">
                    <View className="bg-white w-full max-w-sm rounded-2xl p-5 shadow-2xl">
                        <View className="flex-row justify-between items-center mb-4">
                            <Text className="text-lg font-bold text-gray-900">Family Member Details</Text>
                            <TouchableOpacity onPress={() => setViewModalVisible(false)} className="bg-gray-100 p-1 rounded-full">
                                <Ionicons name="close" size={20} color="gray" />
                            </TouchableOpacity>
                        </View>

                        {selectedMember && (
                            <ScrollView className="max-h-[80%]">
                                <View className="items-center mb-4">
                                    <Image
                                        source={{ uri: selectedMember.photo ? `${FAMILY_PHOTO_URL}${selectedMember.photo}` : 'https://via.placeholder.com/100' }}
                                        className="w-24 h-24 rounded-full bg-gray-200 mb-2 border-2 border-orange-100"
                                    />
                                    <Text className="text-xl font-bold text-gray-800">{selectedMember.name}</Text>
                                    <Text className="text-orange-600 font-medium">{selectedMember.relation}</Text>
                                </View>

                                <View className="space-y-3">
                                    <View className="flex-row border-b border-gray-100 py-2">
                                        <Text className="w-28 text-gray-500">Gender</Text>
                                        <Text className="flex-1 text-gray-800">{selectedMember.gender || 'N/A'}</Text>
                                    </View>
                                    <View className="flex-row border-b border-gray-100 py-2">
                                        <Text className="w-28 text-gray-500">Marital Status</Text>
                                        <Text className="flex-1 text-gray-800">{selectedMember.marital_status || 'N/A'}</Text>
                                    </View>
                                    <View className="flex-row border-b border-gray-100 py-2">
                                        <Text className="w-28 text-gray-500">Occupation</Text>
                                        <Text className="flex-1 text-gray-800">{selectedMember.occupation || 'N/A'}</Text>
                                    </View>
                                    <View className="flex-row border-b border-gray-100 py-2">
                                        <Text className="w-28 text-gray-500">DOB</Text>
                                        <Text className="flex-1 text-gray-800">{selectedMember.dob || 'N/A'}</Text>
                                    </View>
                                    <View className="flex-row border-b border-gray-100 py-2">
                                        <Text className="w-28 text-gray-500">Education</Text>
                                        <Text className="flex-1 text-gray-800">{selectedMember.education || 'N/A'}</Text>
                                    </View>
                                    <View className="flex-row border-b border-gray-100 py-2">
                                        <Text className="w-28 text-gray-500">Income</Text>
                                        <Text className="flex-1 text-gray-800">{selectedMember.income ? `₹${selectedMember.income}` : 'N/A'}</Text>
                                    </View>
                                    <View className="flex-row border-b border-gray-100 py-2">
                                        <Text className="w-28 text-gray-500">Height</Text>
                                        <Text className="flex-1 text-gray-800">{selectedMember.height || 'N/A'}</Text>
                                    </View>
                                    <View className="flex-row border-b border-gray-100 py-2">
                                        <Text className="w-28 text-gray-500">Weight</Text>
                                        <Text className="flex-1 text-gray-800">{selectedMember.weight || 'N/A'}</Text>
                                    </View>
                                    <View className="flex-row border-b border-gray-100 py-2">
                                        <Text className="w-28 text-gray-500">Caste</Text>
                                        <Text className="flex-1 text-gray-800">{selectedMember.caste || 'N/A'}</Text>
                                    </View>
                                    <View className="flex-row border-b border-gray-100 py-2">
                                        <Text className="w-28 text-gray-500">Kuldevi</Text>
                                        <Text className="flex-1 text-gray-800">{selectedMember.kuldevi || 'N/A'}</Text>
                                    </View>
                                </View>
                            </ScrollView>
                        )}

                        <TouchableOpacity onPress={() => setViewModalVisible(false)} className="mt-6 bg-orange-600 py-3 rounded-xl items-center">
                            <Text className="text-white font-bold">Close</Text>
                        </TouchableOpacity>
                    </View>
                </View>
            </Modal>
        </SafeAreaView>
    );
};

export default PublicProfileScreen;
