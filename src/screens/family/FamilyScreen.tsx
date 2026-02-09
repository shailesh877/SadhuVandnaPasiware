import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, ActivityIndicator, Alert, TextInput, ScrollView, Modal } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import * as ImagePicker from 'expo-image-picker';

const FAMILY_PHOTO_URL = `${API_BASE_URL}/uploads/family/`;

const FamilyScreen = ({ navigation }: any) => {
    const [members, setMembers] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [modalVisible, setModalVisible] = useState(false);
    const [userId, setUserId] = useState<string | null>(null);

    // Form State
    const [name, setName] = useState('');
    const [relation, setRelation] = useState('');
    const [gender, setGender] = useState('Male');
    const [occupation, setOccupation] = useState('');
    const [dob, setDob] = useState('');
    const [maritalStatus, setMaritalStatus] = useState('Unmarried');
    // New Fields
    const [height, setHeight] = useState('');
    const [weight, setWeight] = useState('');
    const [education, setEducation] = useState('');
    const [income, setIncome] = useState('');
    const [caste, setCaste] = useState('');
    const [kuldevi, setKuldevi] = useState('');

    const [photo, setPhoto] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [editId, setEditId] = useState<string | null>(null);

    useEffect(() => {
        AsyncStorage.getItem('user').then(u => {
            if (u) {
                const user = JSON.parse(u);
                setUserId(user.id);
                fetchMembers(user.id);
            }
        });
    }, []);

    const fetchMembers = async (uid: string) => {
        try {
            const res = await api.get(`/manage_family.php?action=fetch&user_id=${uid}`);
            if (res.data.status === 'success') {
                setMembers(res.data.data);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const pickImage = async () => {
        const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ['images'],
            allowsEditing: true,
            aspect: [1, 1],
            quality: 0.7,
        });

        if (!result.canceled) {
            setPhoto(result.assets[0].uri);
        }
    };

    const handleEdit = (item: any) => {
        setEditId(item.id);
        setName(item.name);
        setRelation(item.relation);
        setGender(item.gender || 'Male');
        setOccupation(item.occupation || '');
        setDob(item.dob || '');
        setMaritalStatus(item.marital_status || 'Unmarried');
        setHeight(item.height || '');
        setWeight(item.weight || '');
        setEducation(item.education || '');
        setIncome(item.income || '');
        setCaste(item.caste || '');
        setKuldevi(item.kuldevi || '');
        setPhoto(item.photo ? `${FAMILY_PHOTO_URL}${item.photo}` : null);
        setModalVisible(true);
    };

    const handleAddOrUpdateMember = async () => {
        if (!name || !relation || !userId) {
            Alert.alert("Error", "Name and Relation are required");
            return;
        }

        setSubmitting(true);
        try {
            const formData = new FormData();
            formData.append('action', editId ? 'update' : 'add');
            if (editId) formData.append('id', editId);
            formData.append('user_id', userId);
            formData.append('name', name);
            formData.append('relation', relation);
            formData.append('gender', gender);
            formData.append('occupation', occupation);
            formData.append('dob', dob);
            formData.append('marital_status', maritalStatus);
            formData.append('height', height);
            formData.append('weight', weight);
            formData.append('education', education);
            formData.append('income', income);
            formData.append('caste', caste);
            formData.append('kuldevi', kuldevi);

            if (photo && !photo.startsWith('http')) {
                const filename = photo.split('/').pop();
                const match = /\.(\w+)$/.exec(filename || '');
                const type = match ? `image/${match[1]}` : `image/jpeg`;
                formData.append('photo', { uri: photo, name: filename, type } as any);
            }

            const res = await api.post('/manage_family.php', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (res.data.status === 'success') {
                Alert.alert("Success", editId ? "Member Updated" : "Member Added");
                setModalVisible(false);
                resetForm();
                fetchMembers(userId);
            } else {
                Alert.alert("Error", res.data.message || "Failed to save member");
            }
        } catch (error) {
            console.error(error);
            Alert.alert("Error", "Network request failed");
        } finally {
            setSubmitting(false);
        }
    };

    const resetForm = () => {
        setEditId(null);
        setName('');
        setRelation('');
        setGender('Male');
        setOccupation('');
        setDob('');
        setMaritalStatus('Unmarried');
        setHeight('');
        setWeight('');
        setEducation('');
        setIncome('');
        setCaste('');
        setKuldevi('');
        setPhoto(null);
    };

    const handleDelete = async (id: string) => {
        Alert.alert("Confirm Delete", "Are you sure you want to delete this member?", [
            { text: "Cancel", style: "cancel" },
            {
                text: "Delete", style: "destructive", onPress: async () => {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('id', id);

                        const res = await api.post('/manage_family.php', formData, {
                            headers: { 'Content-Type': 'multipart/form-data' }
                        });

                        if (res.data.status === 'success') {
                            fetchMembers(userId!);
                        }
                    } catch (error) { console.error(error); }
                }
            }
        ]);
    };

    const renderItem = ({ item }: { item: any }) => (
        <View className="bg-white mb-4 p-4 rounded-xl border border-orange-100 shadow-sm flex-row items-center gap-4 mx-4">
            <Image
                source={{ uri: item.photo ? `${FAMILY_PHOTO_URL}${item.photo}` : 'https://via.placeholder.com/100' }}
                className="w-16 h-16 rounded-full bg-gray-200"
            />
            <View className="flex-1">
                <Text className="text-lg font-bold text-gray-900">{item.name}</Text>
                <View className="flex-row flex-wrap gap-2 mt-1">
                    <Text className="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-full">{item.relation}</Text>
                    <Text className="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">{item.gender}</Text>
                    {item.occupation ? <Text className="text-xs bg-blue-50 text-blue-600 px-2 py-1 rounded-full">{item.occupation}</Text> : null}
                    {item.marital_status ? <Text className="text-xs bg-purple-50 text-purple-600 px-2 py-1 rounded-full">{item.marital_status}</Text> : null}
                    {item.dob && <Text className="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">DOB: {item.dob}</Text>}
                    {item.education ? <Text className="text-xs bg-indigo-50 text-indigo-600 px-2 py-1 rounded-full">{item.education}</Text> : null}
                </View>
            </View>
            <View className="flex-col gap-2">
                <TouchableOpacity onPress={() => handleEdit(item)} className="p-2 bg-blue-50 rounded-full">
                    <Ionicons name="create-outline" size={20} color="blue" />
                </TouchableOpacity>
                <TouchableOpacity onPress={() => handleDelete(item.id)} className="p-2 bg-red-50 rounded-full">
                    <Ionicons name="trash-outline" size={20} color="red" />
                </TouchableOpacity>
            </View>
        </View>
    );

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            <View className="flex-row items-center justify-between p-4 border-b border-gray-100">
                <View className="flex-row items-center">
                    <TouchableOpacity onPress={() => navigation.goBack()} className="mr-3">
                        <Ionicons name="arrow-back" size={24} color="black" />
                    </TouchableOpacity>
                    <Text className="text-xl font-bold text-gray-800">Family Members</Text>
                </View>
                <TouchableOpacity onPress={() => { resetForm(); setModalVisible(true); }}>
                    <Ionicons name="add-circle" size={30} color="#ea580c" />
                </TouchableOpacity>
            </View>

            {loading ? (
                <View className="flex-1 justify-center items-center"><ActivityIndicator color="#ea580c" /></View>
            ) : (
                <FlatList
                    data={members}
                    renderItem={renderItem}
                    keyExtractor={item => item.id.toString()}
                    contentContainerStyle={{ paddingVertical: 10 }}
                    ListEmptyComponent={
                        <View className="items-center mt-20 p-4">
                            <Text className="text-gray-500 text-center text-lg">No family members added.</Text>
                            <TouchableOpacity onPress={() => { resetForm(); setModalVisible(true); }} className="mt-4 bg-orange-100 px-4 py-2 rounded-lg">
                                <Text className="text-orange-600 font-bold">Add Member</Text>
                            </TouchableOpacity>
                        </View>
                    }
                />
            )}

            <Modal animationType="slide" visible={modalVisible} onRequestClose={() => setModalVisible(false)} presentationStyle="pageSheet">
                <View className="flex-1 bg-white">
                    <View className="flex-row items-center justify-between p-4 border-b border-gray-100">
                        <Text className="text-xl font-bold text-gray-800">{editId ? 'Edit Member' : 'Add Member'}</Text>
                        <TouchableOpacity onPress={() => setModalVisible(false)}>
                            <Text className="text-gray-500 text-lg">Cancel</Text>
                        </TouchableOpacity>
                    </View>
                    <ScrollView className="flex-1 p-5">
                        <View className="items-center mb-6">
                            <TouchableOpacity onPress={pickImage}>
                                {photo ? (
                                    <Image source={{ uri: photo }} className="w-24 h-24 rounded-full" />
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
                                <TextInput value={name} onChangeText={setName} className="bg-gray-50 p-3 rounded-lg border border-gray-200" />
                            </View>
                            <View>
                                <Text className="mb-1 text-gray-600">Relation *</Text>
                                <TextInput value={relation} onChangeText={setRelation} className="bg-gray-50 p-3 rounded-lg border border-gray-200" placeholder="e.g. Brother" />
                            </View>
                            <View>
                                <Text className="mb-1 text-gray-600">Gender</Text>
                                <View className="flex-row gap-4">
                                    {['Male', 'Female', 'Other'].map(g => (
                                        <TouchableOpacity key={g} onPress={() => setGender(g)} className={`px-4 py-2 rounded-full border ${gender === g ? 'bg-orange-50 border-orange-500' : 'border-gray-200'}`}>
                                            <Text className={gender === g ? 'text-orange-600' : 'text-gray-600'}>{g}</Text>
                                        </TouchableOpacity>
                                    ))}
                                </View>
                            </View>
                            <View>
                                <Text className="mb-1 text-gray-600">Marital Status</Text>
                                <View className="flex-row gap-2 flex-wrap">
                                    {['Unmarried', 'Married', 'Divorced', 'Widow'].map(s => (
                                        <TouchableOpacity key={s} onPress={() => setMaritalStatus(s)} className={`px-3 py-1.5 rounded-full border ${maritalStatus === s ? 'bg-orange-50 border-orange-500' : 'border-gray-200'}`}>
                                            <Text className={`text-xs ${maritalStatus === s ? 'text-orange-600' : 'text-gray-600'}`}>{s}</Text>
                                        </TouchableOpacity>
                                    ))}
                                </View>
                            </View>
                            <View>
                                <Text className="mb-1 text-gray-600">Occupation</Text>
                                <TextInput value={occupation} onChangeText={setOccupation} className="bg-gray-50 p-3 rounded-lg border border-gray-200" placeholder="e.g. Engineer" />
                            </View>
                            <View>
                                <Text className="mb-1 text-gray-600">DOB (YYYY-MM-DD)</Text>
                                <TextInput value={dob} onChangeText={setDob} className="bg-gray-50 p-3 rounded-lg border border-gray-200" placeholder="2000-01-01" />
                            </View>

                            <View className="flex-row gap-4">
                                <View className="flex-1">
                                    <Text className="mb-1 text-gray-600">Height</Text>
                                    <TextInput value={height} onChangeText={setHeight} className="bg-gray-50 p-3 rounded-lg border border-gray-200" placeholder="e.g. 5.9" />
                                </View>
                                <View className="flex-1">
                                    <Text className="mb-1 text-gray-600">Weight</Text>
                                    <TextInput value={weight} onChangeText={setWeight} className="bg-gray-50 p-3 rounded-lg border border-gray-200" placeholder="e.g. 70kg" />
                                </View>
                            </View>

                            <View>
                                <Text className="mb-1 text-gray-600">Education</Text>
                                <TextInput value={education} onChangeText={setEducation} className="bg-gray-50 p-3 rounded-lg border border-gray-200" />
                            </View>

                            <View>
                                <Text className="mb-1 text-gray-600">Monthly Income</Text>
                                <TextInput value={income} onChangeText={setIncome} className="bg-gray-50 p-3 rounded-lg border border-gray-200" keyboardType="numeric" />
                            </View>

                            <View>
                                <Text className="mb-1 text-gray-600">Caste / Samaj</Text>
                                <TextInput value={caste} onChangeText={setCaste} className="bg-gray-50 p-3 rounded-lg border border-gray-200" />
                            </View>

                            <View>
                                <Text className="mb-1 text-gray-600">Kuldevi</Text>
                                <TextInput value={kuldevi} onChangeText={setKuldevi} className="bg-gray-50 p-3 rounded-lg border border-gray-200" />
                            </View>
                        </View>
                    </ScrollView>
                    <View className="p-4 border-t border-gray-100">
                        <TouchableOpacity onPress={handleAddOrUpdateMember} disabled={submitting} className="bg-orange-600 py-4 rounded-xl items-center">
                            {submitting ? <ActivityIndicator color="white" /> : <Text className="text-white font-bold text-lg">{editId ? 'Update Member' : 'Save Member'}</Text>}
                        </TouchableOpacity>
                    </View>
                </View>
            </Modal>
        </SafeAreaView>
    );
};

export default FamilyScreen;
