import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, Alert, ScrollView, ActivityIndicator } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { SafeAreaView } from 'react-native-safe-area-context';
import api from '../../services/api';

const RegisterScreen = ({ navigation }: any) => {
    const [step, setStep] = useState(1);
    const [loading, setLoading] = useState(false);

    // Form Data
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [otp, setOtp] = useState('');
    // State for generatedOtp is no longer needed
    // const [generatedOtp, setGeneratedOtp] = useState('');

    // Step 3 Data
    const [mobile, setMobile] = useState('');
    const [city, setCity] = useState('');
    const [cast, setCast] = useState('');
    const [dob, setDob] = useState('');
    const [gender, setGender] = useState('Male');
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');

    const handleSendOtp = async () => {
        if (!name || !email) {
            Alert.alert("Error", "Please enter Name and Email");
            return;
        }
        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('email', email);
            const res = await api.post('/api_send_otp.php', formData);

            // Backend now returns clean JSON
            if (res.data.status === 'success') {
                Alert.alert("OTP Sent", res.data.message);
                setStep(2);
            } else {
                Alert.alert("Error", res.data.message || "Failed to send OTP. Try again.");
            }
        } catch (e) {
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
            formData.append('email', email);
            formData.append('otp', otp);

            const res = await api.post('/api_verify_otp.php', formData);

            // Backend now returns clean JSON
            if (res.data.status === 'success') {
                setStep(3);
            } else {
                Alert.alert("Error", res.data.message || "Invalid or Expired OTP");
            }
        } catch (e) {
            Alert.alert("Error", "Network error verifying OTP");
        } finally {
            setLoading(false);
        }
    };

    const handleRegister = async () => {
        if (!mobile || !city || !cast || !dob || !password || !confirmPassword) {
            Alert.alert("Error", "All fields are required");
            return;
        }
        if (password !== confirmPassword) {
            Alert.alert("Error", "Passwords do not match");
            return;
        }

        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('name', name);
            formData.append('email', email);
            formData.append('mobile', mobile);
            formData.append('city', city);
            formData.append('cast', cast);
            formData.append('dob', dob);
            formData.append('gender', gender);
            formData.append('password', password);

            const res = await api.post('/register.php', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            if (res.data.status === 'success') {
                Alert.alert("Success", "Registration Successful! Please Login.", [
                    { text: "OK", onPress: () => navigation.goBack() }
                ]);
            } else {
                Alert.alert("Error", res.data.message || "Registration failed");
            }
        } catch (e) {
            Alert.alert("Error", "Network error");
        } finally {
            setLoading(false);
        }
    };

    const renderStep1 = () => (
        <View className="space-y-4">
            <View className="flex-row items-center bg-gray-50 border border-gray-200 rounded-xl px-4 py-3">
                <Ionicons name="person-outline" size={20} color="gray" />
                <TextInput
                    className="flex-1 ml-3 text-gray-700 text-base"
                    placeholder="Full Name"
                    value={name}
                    onChangeText={setName}
                    placeholderTextColor="#9ca3af"
                />
            </View>
            <View className="flex-row items-center bg-gray-50 border border-gray-200 rounded-xl px-4 py-3">
                <Ionicons name="mail-outline" size={20} color="gray" />
                <TextInput
                    className="flex-1 ml-3 text-gray-700 text-base"
                    placeholder="Email Address"
                    value={email}
                    onChangeText={setEmail}
                    autoCapitalize="none"
                    placeholderTextColor="#9ca3af"
                />
            </View>
            <TouchableOpacity
                className="bg-orange-600 p-4 rounded-xl items-center shadow-lg shadow-orange-200 mt-6"
                onPress={handleSendOtp}
                disabled={loading}
            >
                {loading ? <ActivityIndicator color="#fff" /> : <Text className="text-white font-bold text-lg">Send OTP</Text>}
            </TouchableOpacity>
        </View>
    );

    const renderStep2 = () => (
        <View className="space-y-4">
            <Text className="text-center text-gray-500 mb-4">OTP sent to {email}</Text>
            <View className="flex-row items-center bg-gray-50 border border-gray-200 rounded-xl px-4 py-3">
                <Ionicons name="key-outline" size={20} color="gray" />
                <TextInput
                    className="flex-1 ml-3 text-gray-700 text-base"
                    placeholder="Enter OTP"
                    value={otp}
                    onChangeText={setOtp}
                    keyboardType="number-pad"
                    placeholderTextColor="#9ca3af"
                />
            </View>
            <TouchableOpacity
                className="bg-orange-600 p-4 rounded-xl items-center shadow-lg shadow-orange-200 mt-6"
                onPress={handleVerifyOtp}
            >
                <Text className="text-white font-bold text-lg">Verify & Proceed</Text>
            </TouchableOpacity>
        </View>
    );

    const renderStep3 = () => (
        <View className="space-y-4">
            <View className="flex-row items-center bg-gray-50 border border-gray-200 rounded-xl px-4 py-3">
                <Ionicons name="call-outline" size={20} color="gray" />
                <TextInput
                    className="flex-1 ml-3 text-gray-700 text-base"
                    placeholder="Mobile Number"
                    value={mobile}
                    onChangeText={setMobile}
                    keyboardType="phone-pad"
                    placeholderTextColor="#9ca3af"
                />
            </View>
            <View className="flex-row items-center bg-gray-50 border border-gray-200 rounded-xl px-4 py-3">
                <Ionicons name="business-outline" size={20} color="gray" />
                <TextInput
                    className="flex-1 ml-3 text-gray-700 text-base"
                    placeholder="City"
                    value={city}
                    onChangeText={setCity}
                    placeholderTextColor="#9ca3af"
                />
            </View>
            <View className="flex-row items-center bg-gray-50 border border-gray-200 rounded-xl px-4 py-3">
                <Ionicons name="people-outline" size={20} color="gray" />
                <TextInput
                    className="flex-1 ml-3 text-gray-700 text-base"
                    placeholder="Community / Cast"
                    value={cast}
                    onChangeText={setCast}
                    placeholderTextColor="#9ca3af"
                />
            </View>
            <View className="flex-row items-center bg-gray-50 border border-gray-200 rounded-xl px-4 py-3">
                <Ionicons name="calendar-outline" size={20} color="gray" />
                <TextInput
                    className="flex-1 ml-3 text-gray-700 text-base"
                    placeholder="Date of Birth (DD-MM-YYYY)"
                    value={dob}
                    onChangeText={setDob}
                    placeholderTextColor="#9ca3af"
                />
            </View>

            {/* Gender Selection */}
            <View className="flex-row justify-between mb-2">
                {['Male', 'Female'].map((g) => (
                    <TouchableOpacity
                        key={g}
                        onPress={() => setGender(g)}
                        className={`flex-1 p-3 rounded-xl border items-center mx-1 ${gender === g ? 'bg-orange-100 border-orange-500' : 'bg-gray-50 border-gray-200'}`}
                    >
                        <Text className={gender === g ? 'text-orange-600 font-bold' : 'text-gray-600'}>{g}</Text>
                    </TouchableOpacity>
                ))}
            </View>

            <View className="flex-row items-center bg-gray-50 border border-gray-200 rounded-xl px-4 py-3">
                <Ionicons name="lock-closed-outline" size={20} color="gray" />
                <TextInput
                    className="flex-1 ml-3 text-gray-700 text-base"
                    placeholder="Create Password"
                    value={password}
                    onChangeText={setPassword}
                    secureTextEntry
                    placeholderTextColor="#9ca3af"
                />
            </View>
            <View className="flex-row items-center bg-gray-50 border border-gray-200 rounded-xl px-4 py-3">
                <Ionicons name="lock-closed-outline" size={20} color="gray" />
                <TextInput
                    className="flex-1 ml-3 text-gray-700 text-base"
                    placeholder="Confirm Password"
                    value={confirmPassword}
                    onChangeText={setConfirmPassword}
                    secureTextEntry
                    placeholderTextColor="#9ca3af"
                />
            </View>

            <TouchableOpacity
                className="bg-orange-600 p-4 rounded-xl items-center shadow-lg shadow-orange-200 mt-6"
                onPress={handleRegister}
                disabled={loading}
            >
                {loading ? <ActivityIndicator color="#fff" /> : <Text className="text-white font-bold text-lg">Sign Up</Text>}
            </TouchableOpacity>
        </View>
    );

    return (
        <SafeAreaView className="flex-1 bg-white">
            <ScrollView contentContainerStyle={{ padding: 24 }}>
                <View className="mb-8 mt-4">
                    <TouchableOpacity onPress={() => step > 1 ? setStep(step - 1) : navigation.goBack()} className="mb-4">
                        <Ionicons name="arrow-back" size={24} color="#4b5563" />
                    </TouchableOpacity>
                    <Text className="text-3xl font-extrabold text-gray-800">
                        {step === 1 ? 'Create Account' : step === 2 ? 'Verify Email' : 'Personal Details'}
                    </Text>
                    <Text className="text-gray-500 mt-2 font-medium">Step {step} of 3</Text>
                </View>

                {step === 1 && renderStep1()}
                {step === 2 && renderStep2()}
                {step === 3 && renderStep3()}

                {step === 1 && (
                    <View className="flex-row justify-center mt-8 mb-10">
                        <Text className="text-gray-500">Already have an account? </Text>
                        <TouchableOpacity onPress={() => navigation.goBack()}>
                            <Text className="text-orange-600 font-bold">Login</Text>
                        </TouchableOpacity>
                    </View>
                )}
            </ScrollView>
        </SafeAreaView>
    );
};

export default RegisterScreen;
