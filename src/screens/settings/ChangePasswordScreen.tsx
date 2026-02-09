import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, Alert, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api from '../../services/api';

const ChangePasswordScreen = () => {
    const navigation = useNavigation();
    const [current, setCurrent] = useState('');
    const [newPass, setNewPass] = useState('');
    const [confirm, setConfirm] = useState('');
    const [loading, setLoading] = useState(false);

    const checkPassword = (str: string) => {
        // Just minimal validation for now
        return str.length >= 6;
    };

    const handleChange = async () => {
        if (!current || !newPass || !confirm) {
            Alert.alert("Error", "All fields are required.");
            return;
        }
        if (newPass !== confirm) {
            Alert.alert("Error", "New passwords do not match.");
            return;
        }
        if (!checkPassword(newPass)) {
            Alert.alert("Error", "Password must be at least 6 characters.");
            return;
        }

        setLoading(true);
        try {
            const uStr = await AsyncStorage.getItem('user');
            if (!uStr) return;
            const user = JSON.parse(uStr);

            const fd = new FormData();
            fd.append('user_id', user.id);
            fd.append('current_password', current);
            fd.append('new_password', newPass);

            const res = await api.post('/change_password.php', fd);

            if (res.data.status === 'success') {
                Alert.alert("Success", "Password updated successfully!", [
                    { text: "OK", onPress: () => navigation.goBack() }
                ]);
            } else {
                Alert.alert("Error", res.data.message || "Failed to update password.");
            }
        } catch (error) {
            console.error(error);
            Alert.alert("Error", "Something went wrong.");
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView style={{ flex: 1, backgroundColor: 'white' }}>
            <View style={{ flexDirection: 'row', alignItems: 'center', padding: 16, borderBottomWidth: 1, borderBottomColor: '#f3f4f6' }}>
                <TouchableOpacity onPress={() => navigation.goBack()}>
                    <Ionicons name="arrow-back" size={24} color="black" />
                </TouchableOpacity>
                <Text style={{ fontSize: 20, fontWeight: 'bold', marginLeft: 16 }}>Change Password</Text>
            </View>

            <View style={{ padding: 20, gap: 20 }}>

                <View>
                    <Text style={{ fontWeight: '600', marginBottom: 8, color: '#374151' }}>Current Password</Text>
                    <TextInput
                        secureTextEntry
                        style={{ borderWidth: 1, borderColor: '#d1d5db', borderRadius: 8, padding: 12 }}
                        value={current} onChangeText={setCurrent}
                        placeholder="Enter current password"
                    />
                </View>

                <View>
                    <Text style={{ fontWeight: '600', marginBottom: 8, color: '#374151' }}>New Password</Text>
                    <TextInput
                        secureTextEntry
                        style={{ borderWidth: 1, borderColor: '#d1d5db', borderRadius: 8, padding: 12 }}
                        value={newPass} onChangeText={setNewPass}
                        placeholder="Min 6 characters"
                    />
                </View>

                <View>
                    <Text style={{ fontWeight: '600', marginBottom: 8, color: '#374151' }}>Confirm New Password</Text>
                    <TextInput
                        secureTextEntry
                        style={{ borderWidth: 1, borderColor: '#d1d5db', borderRadius: 8, padding: 12 }}
                        value={confirm} onChangeText={setConfirm}
                        placeholder="Re-enter new password"
                    />
                </View>

                <TouchableOpacity
                    onPress={handleChange}
                    disabled={loading}
                    style={{ backgroundColor: '#ea580c', padding: 16, borderRadius: 12, alignItems: 'center', marginTop: 10, opacity: loading ? 0.7 : 1 }}
                >
                    {loading ? <ActivityIndicator color="white" /> : (
                        <Text style={{ color: 'white', fontWeight: 'bold', fontSize: 16 }}>Update Password</Text>
                    )}
                </TouchableOpacity>

            </View>
        </SafeAreaView>
    );
};

export default ChangePasswordScreen;
