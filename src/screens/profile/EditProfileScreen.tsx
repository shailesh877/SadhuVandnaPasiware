import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, ScrollView, Image, Alert, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api, { API_BASE_URL } from '../../services/api';

const PHOTO_URL = `${API_BASE_URL}/uploads/photo/`;

const EditProfileScreen = ({ navigation }: any) => {
    const [name, setName] = useState('');
    const [bio, setBio] = useState('');
    const [location, setLocation] = useState('');
    const [dob, setDob] = useState('');
    const [cast, setCast] = useState('');
    const [gender, setGender] = useState('');
    const [avatar, setAvatar] = useState<string | null>(null);
    const [currentPhoto, setCurrentPhoto] = useState<string | null>(null);
    const [cover, setCover] = useState<string | null>(null);
    const [currentCover, setCurrentCover] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);
    const [user, setUser] = useState<any>(null);

    useEffect(() => {
        loadUser();
    }, []);

    const loadUser = async () => {
        const u = await AsyncStorage.getItem('user');
        if (u) {
            const localUser = JSON.parse(u);
            setUser(localUser);
            // Fetch fresh data
            try {
                const res = await api.get(`/get_user_details.php?user_id=${localUser.id}`);
                if (res.data.status === 'success') {
                    const freshUser = res.data.data;
                    setUser(freshUser);
                    setName(freshUser.name || '');
                    setLocation(freshUser.city || '');
                    setDob(freshUser.dob || '');
                    setCast(freshUser.cast || '');
                    setGender(freshUser.gender || '');
                    setCurrentPhoto(freshUser.profile_photo);
                    setCurrentCover(freshUser.cover_photo);
                }
            } catch (error) {
                console.error("Failed to load profile", error);
            }
        }
    };

    const pickImage = async () => {
        const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ['images'],
            allowsEditing: true,
            aspect: [1, 1],
            quality: 0.8,
        });

        if (!result.canceled) {
            setAvatar(result.assets[0].uri);
        }
    };

    const pickCover = async () => {
        const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ['images'],
            allowsEditing: true, // Cover usually wider, maybe [16, 9]?
            aspect: [16, 9],
            quality: 0.8,
        });

        if (!result.canceled) {
            setCover(result.assets[0].uri);
        }
    };

    const handleSave = async () => {
        if (!name) {
            Alert.alert("Error", "Name is required");
            return;
        }

        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('user_id', user?.id);
            formData.append('name', name);
            formData.append('city', location);
            formData.append('dob', dob);
            formData.append('cast', cast);
            formData.append('gender', gender);

            if (avatar) {
                const filename = avatar.split('/').pop();
                const match = /\.(\w+)$/.exec(filename || '');
                const type = match ? `image/${match[1]}` : `image/jpeg`;
                formData.append('photo', { uri: avatar, name: filename, type } as any);
            }

            if (cover) {
                const filename = cover.split('/').pop();
                const match = /\.(\w+)$/.exec(filename || '');
                const type = match ? `image/${match[1]}` : `image/jpeg`;
                formData.append('cover', { uri: cover, name: filename, type } as any);
            }

            const res = await api.post('/update_profile.php', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (res.data.status === 'success') {
                const updatedUser = { ...user, ...res.data.user };
                await AsyncStorage.setItem('user', JSON.stringify(updatedUser)); // Update local session
                Alert.alert('Success', 'Profile updated successfully');
                navigation.goBack();
            } else {
                Alert.alert("Error", res.data.message || "Failed to update profile");
            }
        } catch (error) {
            console.error(error);
            Alert.alert("Error", "Network request failed");
        } finally {
            setLoading(false);
        }
    };

    const displayImage = avatar
        ? avatar
        : (currentPhoto ? `${PHOTO_URL}${currentPhoto}` : null);

    const displayCover = cover
        ? cover
        : (currentCover ? `${PHOTO_URL}${currentCover}` : null);

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            <View className="flex-row items-center justify-between px-4 py-3 border-b border-gray-100">
                <TouchableOpacity onPress={() => navigation.goBack()}>
                    <Text className="text-lg text-gray-500">Cancel</Text>
                </TouchableOpacity>
                <Text className="font-bold text-lg text-gray-900">Edit Profile</Text>
                <TouchableOpacity onPress={handleSave} disabled={loading}>
                    {loading ? <ActivityIndicator color="#ea580c" /> : <Text className="text-lg font-bold text-orange-600">Save</Text>}
                </TouchableOpacity>
            </View>

            <ScrollView className="flex-1">
                {/* Cover Photo */}
                <TouchableOpacity onPress={pickCover} className="h-40 bg-gray-200 w-full relative">
                    {displayCover && (
                        <Image source={{ uri: displayCover }} className="w-full h-full" resizeMode="cover" />
                    )}
                    <View className="absolute inset-0 items-center justify-center bg-black/20">
                        <View className="bg-black/50 p-2 rounded-full">
                            <Ionicons name="camera" size={24} color="white" />
                        </View>
                    </View>
                </TouchableOpacity>

                {/* Profile Photo - Overlapping */}
                <View className="items-center -mt-12 mb-4">
                    <TouchableOpacity onPress={pickImage} className="relative">
                        {displayImage ? (
                            <Image source={{ uri: displayImage }} className="w-24 h-24 rounded-full bg-gray-200 border-4 border-white" />
                        ) : (
                            <View className="w-24 h-24 rounded-full bg-orange-200 items-center justify-center border-4 border-white">
                                <Text className="text-4xl text-orange-700">{name?.[0] || 'U'}</Text>
                            </View>
                        )}
                        <View className="absolute bottom-0 right-0 bg-orange-600 p-1.5 rounded-full border-2 border-white">
                            <Ionicons name="camera" size={16} color="white" />
                        </View>
                    </TouchableOpacity>
                </View>

                <View className="px-5 space-y-6">
                    <View>
                        <Text className="text-gray-500 text-sm mb-1 ml-1">Full Name</Text>
                        <TextInput
                            value={name}
                            onChangeText={setName}
                            className="bg-gray-50 p-4 rounded-xl text-gray-800 text-base border border-gray-100"
                        />
                    </View>
                    <View>
                        <Text className="text-gray-500 text-sm mb-1 ml-1">City</Text>
                        <TextInput
                            value={location} // Using location state for city
                            onChangeText={setLocation}
                            className="bg-gray-50 p-4 rounded-xl text-gray-800 text-base border border-gray-100"
                        />
                    </View>
                    <View>
                        <Text className="text-gray-500 text-sm mb-1 ml-1">Date of Birth (YYYY-MM-DD)</Text>
                        <TextInput
                            value={dob}
                            onChangeText={setDob}
                            className="bg-gray-50 p-4 rounded-xl text-gray-800 text-base border border-gray-100"
                        />
                    </View>
                    <View>
                        <Text className="text-gray-500 text-sm mb-1 ml-1">Cast</Text>
                        <TextInput
                            value={cast}
                            onChangeText={setCast}
                            className="bg-gray-50 p-4 rounded-xl text-gray-800 text-base border border-gray-100"
                        />
                    </View>
                    <View>
                        <Text className="text-gray-500 text-sm mb-1 ml-1">Gender</Text>
                        <TextInput
                            value={gender}
                            onChangeText={setGender}
                            className="bg-gray-50 p-4 rounded-xl text-gray-800 text-base border border-gray-100"
                        />
                    </View>
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

export default EditProfileScreen;
