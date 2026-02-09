import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, Alert, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import api from '../../services/api';

const ForgotPasswordScreen = ({ navigation }: any) => {
    const [step, setStep] = useState(1);
    const [email, setEmail] = useState('');
    const [otp, setOtp] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [loading, setLoading] = useState(false);

    const handleSendOtp = async () => {
        if (!email) {
            Alert.alert("Error", "Please enter your email");
            return;
        }
        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('email', email);
            const res = await api.post('/api_forgot_send_otp.php', formData);
            if (res.data.status === 'success') {
                Alert.alert("OTP Sent", "Please check your email for OTP.");
                setStep(2);
            } else {
                Alert.alert("Error", res.data.message || "Failed to send OTP");
            }
        } catch (error) {
            Alert.alert("Error", "Network error");
        } finally {
            setLoading(false);
        }
    };

    const handleVerifyOtp = async () => {
        if (!otp) {
            Alert.alert("Error", "Please enter OTP");
            return;
        }
        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('otp', otp);
            const res = await api.post('/api_forgot_verify_otp.php', formData);
            if (res.data.status === 'success') {
                setStep(3);
            } else {
                Alert.alert("Error", res.data.message || "Invalid OTP");
            }
        } catch (error) {
            Alert.alert("Error", "Network error");
        } finally {
            setLoading(false);
        }
    };

    const handleResetPassword = async () => {
        if (!newPassword || !confirmPassword) {
            Alert.alert("Error", "Please fill all fields");
            return;
        }
        if (newPassword !== confirmPassword) {
            Alert.alert("Error", "Passwords do not match");
            return;
        }
        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('password', newPassword);
            const res = await api.post('/api_forgot_reset_password.php', formData);
            if (res.data.status === 'success') {
                Alert.alert("Success", "Password reset successfully!", [
                    { text: "OK", onPress: () => navigation.goBack() }
                ]);
            } else {
                Alert.alert("Error", res.data.message || "Failed to reset password");
            }
        } catch (error) {
            Alert.alert("Error", "Network error");
        } finally {
            setLoading(false);
        }
    };

    const renderStep1 = () => (
        <View className="w-full">
            <Text className="text-gray-500 text-center mb-6">Enter your registered email to receive an OTP.</Text>
            <View className="flex-row items-center bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 mb-6">
                <Ionicons name="mail-outline" size={20} color="gray" />
                <TextInput
                    className="flex-1 ml-3 text-gray-700 text-base"
                    placeholder="Email Address"
                    value={email}
                    onChangeText={setEmail}
                    autoCapitalize="none"
                    keyboardType="email-address"
                />
            </View>
            <TouchableOpacity
                className="w-full bg-orange-600 py-4 rounded-xl items-center shadow-lg shadow-orange-200"
                onPress={handleSendOtp}
                disabled={loading}
            >
                {loading ? <ActivityIndicator color="white" /> : <Text className="text-white font-bold text-lg">Send OTP</Text>}
            </TouchableOpacity>
        </View>
    );

    const renderStep2 = () => (
        <View className="w-full">
            <Text className="text-gray-500 text-center mb-6">Enter the 6-digit OTP sent to {email}</Text>
            <View className="flex-row items-center bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 mb-6">
                <Ionicons name="key-outline" size={20} color="gray" />
                <TextInput
                    className="flex-1 ml-3 text-gray-700 text-base"
                    placeholder="Enter OTP"
                    value={otp}
                    onChangeText={setOtp}
                    keyboardType="number-pad"
                    maxLength={6}
                />
            </View>
            <TouchableOpacity
                className="w-full bg-green-600 py-4 rounded-xl items-center shadow-lg shadow-green-200"
                onPress={handleVerifyOtp}
                disabled={loading}
            >
                {loading ? <ActivityIndicator color="white" /> : <Text className="text-white font-bold text-lg">Verify OTP</Text>}
            </TouchableOpacity>
        </View>
    );

    const renderStep3 = () => (
        <View className="w-full">
            <Text className="text-gray-500 text-center mb-6">Create a new password for your account.</Text>
            <View className="flex-row items-center bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 mb-4">
                <Ionicons name="lock-closed-outline" size={20} color="gray" />
                <TextInput
                    className="flex-1 ml-3 text-gray-700 text-base"
                    placeholder="New Password"
                    value={newPassword}
                    onChangeText={setNewPassword}
                    secureTextEntry
                />
            </View>
            <View className="flex-row items-center bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 mb-6">
                <Ionicons name="lock-closed-outline" size={20} color="gray" />
                <TextInput
                    className="flex-1 ml-3 text-gray-700 text-base"
                    placeholder="Confirm Password"
                    value={confirmPassword}
                    onChangeText={setConfirmPassword}
                    secureTextEntry
                />
            </View>
            <TouchableOpacity
                className="w-full bg-orange-600 py-4 rounded-xl items-center shadow-lg shadow-orange-200"
                onPress={handleResetPassword}
                disabled={loading}
            >
                {loading ? <ActivityIndicator color="white" /> : <Text className="text-white font-bold text-lg">Reset Password</Text>}
            </TouchableOpacity>
        </View>
    );

    return (
        <SafeAreaView className="flex-1 bg-white px-6 py-10">
            <TouchableOpacity onPress={() => step > 1 ? setStep(step - 1) : navigation.goBack()} className="absolute top-12 left-6 z-10 p-2">
                <Ionicons name="arrow-back" size={24} color="#4b5563" />
            </TouchableOpacity>

            <View className="items-center mt-10 mb-8">
                <View className="w-20 h-20 bg-orange-100 rounded-full items-center justify-center mb-4">
                    <Ionicons name={step === 3 ? "lock-open" : "shield-checkmark-outline"} size={40} color="#ea580c" />
                </View>
                <Text className="text-2xl font-bold text-gray-800">
                    {step === 1 ? 'Forgot Password?' : step === 2 ? 'Verify Identity' : 'Reset Password'}
                </Text>
            </View>

            {step === 1 && renderStep1()}
            {step === 2 && renderStep2()}
            {step === 3 && renderStep3()}
        </SafeAreaView>
    );
};

export default ForgotPasswordScreen;
