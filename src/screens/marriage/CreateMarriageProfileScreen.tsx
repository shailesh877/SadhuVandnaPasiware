import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, ScrollView, Image, Alert, ActivityIndicator, KeyboardAvoidingView, Platform } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api from '../../services/api';

const CreateMarriageProfileScreen = ({ navigation }: any) => {
    const [formData, setFormData] = useState({
        full_name: '',
        gender: '',
        dob: '',
        status: '', // Marital Status
        height: '',
        weight: '',
        religion: '',
        phone: '',
        email: '',
        education: '',
        occupation: '',
        work_place: '',
        income: '',
        city: '',
        residence: '', // State/Residence
        caste: '',
        about: '',

        // Family
        father_name: '',
        father_occupation: '',
        mother_name: '',
        siblings: '',
        family_type: '',

        // Personal
        nature: '',
        food: '',
        habits: '',
        hobbies: '',

        // Partner Preference
        partner_age_from: '',
        partner_age_to: '',
        partner_education: '',
        partner_expectations: ''
    });

    const [photo, setPhoto] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);
    const [userId, setUserId] = useState<string | null>(null);
    const [isEdit, setIsEdit] = useState(false);

    useEffect(() => {
        // Pre-fill if editing
        const routeParams = (navigation as any).getState().routes.find((r: any) => r.name === 'CreateMarriageProfile')?.params;
        if (routeParams?.profile) {
            const { profile } = routeParams;
            setFormData({
                full_name: profile.full_name || '',
                gender: profile.gender || '',
                dob: profile.dob || '',
                status: profile.status || '',
                height: profile.height || '',
                weight: profile.weight || '',
                religion: profile.religion || '',
                phone: profile.phone || '',
                email: profile.email || '',
                education: profile.education || '',
                occupation: profile.occupation || '',
                work_place: profile.work_place || '',
                income: profile.income || '',
                city: profile.city || '',
                residence: profile.residence || '',
                caste: profile.caste || '',
                about: profile.about || '',

                father_name: profile.father_name || '',
                father_occupation: profile.father_occupation || '',
                mother_name: profile.mother_name || '',
                siblings: profile.siblings || '',
                family_type: profile.family_type || '',

                nature: profile.nature || '',
                food: profile.food || '',
                habits: profile.habits || '',
                hobbies: profile.hobbies || '',

                partner_age_from: profile.partner_age_from || '',
                partner_age_to: profile.partner_age_to || '',
                partner_education: profile.partner_education || '',
                partner_expectations: profile.partner_expectations || ''
            });

            if (profile.photo && !profile.photo.includes('default')) {
                // We don't set 'photo' uri directly from server URL as ImagePicker/Upload logic expects local validation mostly, 
                // but we can leave it null to indicate "no new photo selected"
            }
            setIsEdit(true);
        }

        AsyncStorage.getItem('user').then(u => {
            if (u) {
                const user = JSON.parse(u);
                setUserId(user.id);
                // Auto-fill from main profile if not edit
                if (!isEdit && !routeParams?.profile) {
                    setFormData(prev => ({
                        ...prev,
                        full_name: user.name,
                        city: user.city || '',
                        phone: user.mobile || '',
                        email: user.email || '',
                        dob: user.dob || '',
                        gender: user.gender || '',
                        status: user.maritial_status || '',
                        caste: user.cast || ''
                    }));
                }
            }
        });
    }, []);

    const handleChange = (name: string, value: string) => {
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const pickImage = async () => {
        const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ['images'],
            allowsEditing: true,
            aspect: [1, 1],
            quality: 0.8,
        });

        if (!result.canceled) {
            setPhoto(result.assets[0].uri);
        }
    };

    const handleSubmit = async () => {
        if (!formData.full_name || !formData.dob || !formData.city || !formData.gender || !formData.status || (!photo && !isEdit)) {
            Alert.alert("Error", "Please fill essential fields (Name, DOB, City, Gender, Status) and ensure a photo is added.");
            return;
        }

        setLoading(true);
        try {
            const data = new FormData();
            data.append('user_id', userId || '');
            Object.keys(formData).forEach(key => {
                data.append(key, formData[key as keyof typeof formData]);
            });

            if (photo) {
                const filename = photo.split('/').pop();
                const match = /\.(\w+)$/.exec(filename || '');
                const type = match ? `image/${match[1]}` : `image/jpeg`;
                data.append('photo', { uri: photo, name: filename, type } as any);
            }

            const res = await api.post('/create_marriage_profile.php', data, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (res.data.status === 'success') {
                Alert.alert("Success", isEdit ? "Marriage Profile Updated!" : "Marriage Profile Created!");
                navigation.goBack();
            } else {
                Alert.alert("Error", res.data.message || "Failed to create profile");
            }
        } catch (error) {
            console.error(error);
            Alert.alert("Error", "Network request failed");
        } finally {
            setLoading(false);
        }
    };

    const SectionHeader = ({ title, icon }: any) => (
        <View className="flex-row items-center gap-2 mt-6 mb-3 border-b border-orange-100 pb-2">
            <Ionicons name={icon} size={20} color="#ea580c" />
            <Text className="text-lg font-bold text-orange-700">{title}</Text>
        </View>
    );

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            <View className="flex-row items-center p-4 border-b border-gray-100 bg-white z-10">
                <TouchableOpacity onPress={() => navigation.goBack()} className="mr-3">
                    <Ionicons name="arrow-back" size={24} color="black" />
                </TouchableOpacity>
                <Text className="text-xl font-bold text-gray-800">{isEdit ? 'Edit Matrimony Profile' : 'Create Matrimony Profile'}</Text>
            </View>

            <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} className="flex-1">
                <ScrollView className="flex-1 px-5" showsVerticalScrollIndicator={false}>

                    {/* Photo Upload */}
                    <View className="items-center my-6">
                        <TouchableOpacity onPress={pickImage} className="relative">
                            {photo ? (
                                <Image source={{ uri: photo }} className="w-32 h-32 rounded-full bg-gray-200 border-4 border-orange-100" />
                            ) : (
                                <View className="w-32 h-32 rounded-full bg-orange-50 items-center justify-center border-2 border-dashed border-orange-300">
                                    <Ionicons name="camera" size={36} color="#ea580c" />
                                    <Text className="text-xs text-orange-600 mt-1 font-semibold">Add Photo</Text>
                                </View>
                            )}
                            {isEdit && !photo && <Text className="text-xs text-gray-400 mt-2 text-center">Current photo kept if unchanged</Text>}
                            <View className="absolute bottom-0 right-0 bg-orange-500 rounded-full p-2 border-2 border-white">
                                <Ionicons name="pencil" size={16} color="white" />
                            </View>
                        </TouchableOpacity>
                    </View>

                    {/* Basic Details */}
                    <SectionHeader title="Basic Details" icon="person-outline" />
                    <View className="space-y-4">
                        <Input label="Full Name" value={formData.full_name} onChangeText={(t) => handleChange('full_name', t)} required />

                        <View>
                            <Text className="text-gray-600 text-sm mb-1 ml-1 font-semibold">Gender <Text className="text-red-500">*</Text></Text>
                            <View className="flex-row gap-2">
                                {['Male', 'Female', 'Other'].map(g => (
                                    <TouchableOpacity
                                        key={g}
                                        onPress={() => handleChange('gender', g)}
                                        className={`flex-1 py-3 items-center rounded-xl border ${formData.gender === g ? 'bg-orange-500 border-orange-500' : 'bg-gray-50 border-gray-200'}`}
                                    >
                                        <Text className={formData.gender === g ? 'text-white font-bold' : 'text-gray-600'}>{g}</Text>
                                    </TouchableOpacity>
                                ))}
                            </View>
                        </View>

                        <Input label="Date of Birth (YYYY-MM-DD)" value={formData.dob} onChangeText={(t) => handleChange('dob', t)} placeholder="2000-01-01" required />

                        <View className="flex-row gap-3">
                            <View className="flex-1">
                                <Input label="Height" value={formData.height} onChangeText={(t) => handleChange('height', t)} placeholder="5 ft 10 in" />
                            </View>
                            <View className="flex-1">
                                <Input label="Weight" value={formData.weight} onChangeText={(t) => handleChange('weight', t)} placeholder="65 kg" />
                            </View>
                        </View>

                        <View>
                            <Text className="text-gray-600 text-sm mb-1 ml-1 font-semibold">Marital Status <Text className="text-red-500">*</Text></Text>
                            <View className="flex-row flex-wrap gap-2">
                                {['Unmarried', 'Married', 'Divorced', 'Widowed', 'Separated'].map(s => (
                                    <TouchableOpacity
                                        key={s}
                                        onPress={() => handleChange('status', s)}
                                        className={`px-3 py-2 rounded-lg border ${formData.status === s ? 'bg-orange-500 border-orange-500' : 'bg-gray-50 border-gray-200'}`}
                                    >
                                        <Text className={formData.status === s ? 'text-white text-xs font-bold' : 'text-gray-600 text-xs'}>{s}</Text>
                                    </TouchableOpacity>
                                ))}
                            </View>
                        </View>

                        <Input label="Religion" value={formData.religion} onChangeText={(t) => handleChange('religion', t)} />
                        <Input label="Caste/Community" value={formData.caste} onChangeText={(t) => handleChange('caste', t)} />
                    </View>

                    {/* Contact & Location */}
                    <SectionHeader title="Contact & Location" icon="location-outline" />
                    <View className="space-y-4">
                        <Input label="Phone Number" value={formData.phone} onChangeText={(t) => handleChange('phone', t)} keyboardType="phone-pad" required />
                        <Input label="Email ID" value={formData.email} onChangeText={(t) => handleChange('email', t)} keyboardType="email-address" />
                        <Input label="City" value={formData.city} onChangeText={(t) => handleChange('city', t)} required />
                        <Input label="Residence Address" value={formData.residence} onChangeText={(t) => handleChange('residence', t)} multiline />
                    </View>

                    {/* Career */}
                    <SectionHeader title="Education & Career" icon="briefcase-outline" />
                    <View className="space-y-4">
                        <Input label="Education" value={formData.education} onChangeText={(t) => handleChange('education', t)} />
                        <Input label="Occupation" value={formData.occupation} onChangeText={(t) => handleChange('occupation', t)} />
                        <Input label="Work Place" value={formData.work_place} onChangeText={(t) => handleChange('work_place', t)} />
                        <Input label="Annual Income" value={formData.income} onChangeText={(t) => handleChange('income', t)} keyboardType="numeric" />
                    </View>

                    {/* Family */}
                    <SectionHeader title="Family Details" icon="people-outline" />
                    <View className="space-y-4">
                        <Input label="Father's Name" value={formData.father_name} onChangeText={(t) => handleChange('father_name', t)} />
                        <Input label="Father's Occupation" value={formData.father_occupation} onChangeText={(t) => handleChange('father_occupation', t)} />
                        <Input label="Mother's Name" value={formData.mother_name} onChangeText={(t) => handleChange('mother_name', t)} />
                        <Input label="Siblings (Brothers/Sisters)" value={formData.siblings} onChangeText={(t) => handleChange('siblings', t)} placeholder="2 Brothers, 1 Sister" />
                        <Input label="Family Type" value={formData.family_type} onChangeText={(t) => handleChange('family_type', t)} placeholder="Nuclear / Joint" />
                    </View>

                    {/* Personal */}
                    <SectionHeader title="Personal Details" icon="cafe-outline" />
                    <View className="space-y-4">
                        <Input label="Nature / Personality" value={formData.nature} onChangeText={(t) => handleChange('nature', t)} placeholder="Calm, Cheerful..." />
                        <Input label="Food Habit" value={formData.food} onChangeText={(t) => handleChange('food', t)} placeholder="Vegetarian / Non-Veg" />
                        <Input label="Habits (Drinking/Smoking)" value={formData.habits} onChangeText={(t) => handleChange('habits', t)} placeholder="No Smoking / Drinking" />
                        <Input label="Hobbies" value={formData.hobbies} onChangeText={(t) => handleChange('hobbies', t)} placeholder="Reading, Traveling..." />

                        <View>
                            <Text className="text-gray-600 text-sm mb-1 ml-1 font-semibold">About Me</Text>
                            <TextInput
                                className="bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 h-24"
                                value={formData.about}
                                onChangeText={(t) => handleChange('about', t)}
                                placeholder="Tell us about yourself..."
                                placeholderTextColor="#9ca3af"
                                multiline
                                textAlignVertical="top"
                            />
                        </View>
                    </View>

                    {/* Partner Preferences */}
                    <SectionHeader title="Partner Preference" icon="heart-outline" />
                    <View className="space-y-4">
                        <View className="flex-row gap-3">
                            <View className="flex-1">
                                <Input label="Age From" value={formData.partner_age_from} onChangeText={(t) => handleChange('partner_age_from', t)} keyboardType="numeric" />
                            </View>
                            <View className="flex-1">
                                <Input label="Age To" value={formData.partner_age_to} onChangeText={(t) => handleChange('partner_age_to', t)} keyboardType="numeric" />
                            </View>
                        </View>
                        <Input label="Expected Education" value={formData.partner_education} onChangeText={(t) => handleChange('partner_education', t)} />
                        <View>
                            <Text className="text-gray-600 text-sm mb-1 ml-1 font-semibold">Expectations</Text>
                            <TextInput
                                className="bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 h-24"
                                value={formData.partner_expectations}
                                onChangeText={(t) => handleChange('partner_expectations', t)}
                                placeholder="What are you looking for?"
                                placeholderTextColor="#9ca3af"
                                multiline
                                textAlignVertical="top"
                            />
                        </View>
                    </View>

                    <TouchableOpacity
                        className="bg-orange-600 py-4 rounded-xl items-center my-10 shadow-lg"
                        onPress={handleSubmit}
                        disabled={loading}
                    >
                        {loading ? <ActivityIndicator color="white" /> : <Text className="text-white font-bold text-lg">{isEdit ? 'Update Profile' : 'Save Marriage Profile'}</Text>}
                    </TouchableOpacity>

                    <View className="h-10" />
                </ScrollView>
            </KeyboardAvoidingView>
        </SafeAreaView>
    );
};

interface InputProps {
    label: string;
    value: string;
    onChangeText: (text: string) => void;
    placeholder?: string;
    keyboardType?: any;
    multiline?: boolean;
    required?: boolean;
}

const Input = ({ label, value, onChangeText, placeholder, keyboardType, multiline, required }: InputProps) => (
    <View>
        <Text className="text-gray-600 text-sm mb-1 ml-1 font-semibold">
            {label} {required && <Text className="text-red-500">*</Text>}
        </Text>
        <TextInput
            className={`bg-gray-50 border border-gray-200 rounded-xl p-3 text-gray-800 text-base ${multiline ? 'h-20' : ''}`}
            value={value}
            onChangeText={onChangeText}
            placeholder={placeholder}
            placeholderTextColor="#9ca3af"
            keyboardType={keyboardType}
            multiline={multiline}
            textAlignVertical={multiline ? 'top' : 'center'}
        />
    </View>
);

export default CreateMarriageProfileScreen;
