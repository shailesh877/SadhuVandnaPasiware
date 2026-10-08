import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, Alert, ActivityIndicator, ScrollView, Platform, KeyboardAvoidingView } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import api from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useLanguage } from '../../context/LanguageContext';
// Picker removed as Caste selection is hidden
import { OTPWidget } from '@msg91comm/sendotp-react-native';

const ALLOWED_CASTES = [
    "Kapdi", "Deshani", "Dudhrejia", "Danidhariya", "Gondaliya", "Mesvaniya",
    "Ramkabir", "Ramsnehi", "Vaghani", "Chapbai", "Parabiya", "Hariyani",
    "Sarpadadiya", "Ramdevputra", "Ravibhan", "Baroliya"
];

const MSG91_WIDGET_ID  = '36636b6f736b393436333238';
const MSG91_TOKEN_AUTH = '495236TgKMhDKHXV6996e94bP1';

export default function LoginScreen({ navigation }: any) {
    const { t } = useLanguage();

    // Form states
    const [firstName, setFirstName]   = useState('');
    const [middleName, setMiddleName] = useState('');
    const [lastName, setLastName]     = useState('');
    const [caste, setCaste]           = useState('Kapdi');
    const [mobile, setMobile]         = useState('');

    // OTP states
    const [showOtpScreen, setShowOtpScreen] = useState(false);
    const [otp, setOtp]                   = useState('');
    const [reqId, setReqId]               = useState('');
    const [loading, setLoading]           = useState(false);
    const [verifying, setVerifying]       = useState(false);

    // Initialize MSG91 SDK
    useEffect(() => {
        OTPWidget.initializeWidget(MSG91_WIDGET_ID, MSG91_TOKEN_AUTH);
    }, []);

    // Auto-Submit Effect 
    useEffect(() => {
        // MSG91 OTPs are typically 4 or 6 digits
        if (otp && (otp.length === 4 || otp.length === 6)) {
            // Wait a small moment to ensure state is settled and user isn't still typing
            const timer = setTimeout(() => {
                if (!verifying) {
                    handleVerifyOtp();
                }
            }, 800);
            return () => clearTimeout(timer);
        }
    }, [otp]);

    const validateForm = () => {
        if (!firstName || !middleName || !lastName || !mobile) {
            Alert.alert(t('error'), t('fillAllFields'));
            return false;
        }
        if (mobile.length !== 10 || !/^\d+$/.test(mobile)) {
            Alert.alert(t('error'), 'Please enter a valid 10-digit mobile number');
            return false;
        }
        return true;
    };

    const handleGetLoginCode = async () => {
        if (!validateForm()) return;
        
        setLoading(true);
        try {
            // Identifier must be country code + number according to docs: '91758XXXXXXX'
            const data = {
                identifier: '91' + mobile
            };
            const response = await OTPWidget.sendOTP(data);
            
            // Expected response contains reqId if successful
            if (response && response.type === 'success' && response.message) {
                setReqId(response.message); // message contains the reqId in MSG91 V5
                setShowOtpScreen(true);
            } else {
                Alert.alert('Error', response?.message || 'Failed to send OTP via MSG91 SDK.');
            }
        } catch (error: any) {
            Alert.alert('Error', error?.message || 'Network request failed when calling MSG91.');
        } finally {
            setLoading(false);
        }
    };

    // Helper to verify with a specific OTP string (useful for auto-submit)
    const handleVerifyOtpWithCode = async (codeToVerify: string) => {
        if (!codeToVerify || codeToVerify.length < 4) {
            Alert.alert('Error', 'Please enter a valid OTP.');
            return;
        }

        console.log('Starting OTP Verification:', codeToVerify);
        setVerifying(true);
        try {
            const body = {
                reqId: reqId,
                otp: codeToVerify
            };
            console.log('Calling OTPWidget.verifyOTP...');
            const response = await OTPWidget.verifyOTP(body);
            console.log('OTPWidget.verifyOTP response:', response);

            if (response && response.type === 'success') {
                const fullName = `${firstName.trim()} ${middleName.trim()} ${lastName.trim()}`;

                const payload = {
                    mobile: mobile,
                    name: fullName,
                    caste: caste,
                    verified_from_sdk: 'true',
                    otp: codeToVerify
                };

                console.log('Calling backend verify API at:', api.defaults.baseURL + '/api_login_verify_otp.php');
                const backendResponse = await api.post('/api_login_verify_otp.php', payload);
                
                const data = backendResponse.data;
                console.log('Backend response data:', data);

                if (data.status === 'success_login' || data.status === 'success_register') {
                    await AsyncStorage.setItem('user', JSON.stringify(data.user));
                    setShowOtpScreen(false);
                    navigation.reset({ index: 0, routes: [{ name: 'MainTabs' }] });
                } else {
                    Alert.alert('Login Failed', data.message || data.detail || 'Invalid response from server');
                }
            } else {
                Alert.alert('Verification Failed', response?.message || 'Invalid OTP');
            }
        } catch (error: any) {
            console.error('OTP Verification Error:', error);
            let errorMsg = 'Verification request failed.';
            
            if (error.isAxiosError) {
                if (error.code === 'ECONNABORTED') {
                    errorMsg = 'Server connection timed out (30s). Please check your internet or try again later.';
                } else if (!error.response) {
                    errorMsg = `Network Error: Cannot reach server at ${api.defaults.baseURL}. Please verify server is online or try over high-speed internet.`;
                } else {
                    errorMsg = `Server Error (${error.response.status}): ${error.response.data?.message || error.message}`;
                }
            } else {
                errorMsg = error?.message || errorMsg;
            }
            
            Alert.alert('Error', errorMsg);
        } finally {
            console.log('Setting verifying to false');
            setVerifying(false);
        }
    };

    const handleVerifyOtp = () => {
        handleVerifyOtpWithCode(otp);
    };

    return (
        <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'} className="flex-1 bg-[#fffaf5]">
            <ScrollView contentContainerStyle={{ flexGrow: 1, paddingHorizontal: 24, paddingVertical: 40 }}>
                {!showOtpScreen ? (
                    <>
                        <View className="items-center mb-8 mt-10">
                            <View className="bg-orange-100 p-4 rounded-3xl mb-4">
                                <Ionicons name="people" size={40} color="#ff6b00" />
                            </View>
                            <Text className="text-3xl font-extrabold text-[#1a2b4c] text-center">Welcome</Text>
                            <Text className="text-gray-500 font-medium mt-2 text-center text-base">Enter your details to login or create account</Text>
                        </View>

                        <View className="space-y-4">
                            <View>
                                <Text className="text-xs font-bold text-gray-500 mb-1 ml-1">FIRST NAME <Text className="text-red-500">*</Text></Text>
                                <View className="flex-row items-center bg-[#f0f5ff] rounded-xl px-4 py-3">
                                    <Ionicons name="person" size={20} color="#ff6b00" />
                                    <TextInput
                                        className="flex-1 ml-3 text-gray-800 text-base"
                                        placeholder="e.g. Yuvraj"
                                        placeholderTextColor="#9ca3af"
                                        value={firstName}
                                        onChangeText={setFirstName}
                                    />
                                </View>
                            </View>

                            <View>
                                <Text className="text-xs font-bold text-gray-500 mb-1 ml-1">MIDDLE NAME <Text className="text-red-500">*</Text></Text>
                                <View className="flex-row items-center bg-[#f0f5ff] rounded-xl px-4 py-3">
                                    <Ionicons name="person" size={20} color="#ff6b00" />
                                    <TextInput
                                        className="flex-1 ml-3 text-gray-800 text-base"
                                        placeholder="e.g. Singh"
                                        placeholderTextColor="#9ca3af"
                                        value={middleName}
                                        onChangeText={setMiddleName}
                                    />
                                </View>
                            </View>

                            <View>
                                <Text className="text-xs font-bold text-gray-500 mb-1 ml-1">LAST NAME <Text className="text-red-500">*</Text></Text>
                                <View className="flex-row items-center bg-[#f0f5ff] rounded-xl px-4 py-3">
                                    <Ionicons name="people" size={20} color="#ff6b00" />
                                    <TextInput
                                        className="flex-1 ml-3 text-gray-800 text-base"
                                        placeholder="e.g. Jadeja"
                                        placeholderTextColor="#9ca3af"
                                        value={lastName}
                                        onChangeText={setLastName}
                                    />
                                </View>
                            </View>



                            <View>
                                <Text className="text-xs font-bold text-gray-500 mb-1 ml-1">MOBILE NUMBER <Text className="text-red-500">*</Text></Text>
                                <View className="flex-row items-center bg-[#f0f5ff] rounded-xl px-4 py-3">
                                    <Ionicons name="phone-portrait-outline" size={20} color="#6b7280" />
                                    <TextInput
                                        className="flex-1 ml-3 text-gray-800 text-base font-semibold"
                                        placeholder="e.g. 9648022011"
                                        placeholderTextColor="#9ca3af"
                                        value={mobile}
                                        onChangeText={setMobile}
                                        keyboardType="number-pad"
                                        maxLength={10}
                                    />
                                </View>
                            </View>
                        </View>

                        <TouchableOpacity
                            className="w-full bg-[#ff6b00] py-4 rounded-xl items-center shadow-lg shadow-orange-300 mt-8 mb-4"
                            onPress={handleGetLoginCode}
                            disabled={loading}
                        >
                            {loading ? (
                                <ActivityIndicator color="white" />
                            ) : (
                                <View className="flex-row items-center">
                                    <Text className="text-white font-bold text-lg mr-2">Get Login Code</Text>
                                    <Ionicons name="arrow-forward" size={20} color="white" />
                                </View>
                            )}
                        </TouchableOpacity>
                    </>
                ) : (
                    <View className="flex-1 justify-center items-center mt-20">
                        <View className="bg-orange-100 p-5 rounded-full mb-6">
                            <Ionicons name="chatbubble-ellipses-outline" size={50} color="#ff6b00" />
                        </View>
                        <Text className="text-2xl font-bold text-[#1a2b4c] mb-2 text-center">Verify OTP</Text>
                        <Text className="text-gray-500 text-center mb-8 px-4">
                            We've sent a verification code to +91 {mobile}
                        </Text>

                        <View className="w-full bg-white rounded-2xl p-6 shadow-sm shadow-gray-200">
                            <Text className="text-xs font-bold text-gray-400 mb-2">ENTER OTP</Text>
                            <View className="bg-[#f8fafc] rounded-xl px-4 py-2 border border-gray-100 flex-row items-center mb-6">
                                <Ionicons name="keypad-outline" size={24} color="#94a3b8" />
                                <TextInput
                                    className="flex-1 ml-3 text-2xl tracking-widest text-[#1a2b4c] font-bold py-2"
                                    placeholder="• • • • • •"
                                    placeholderTextColor="#cbd5e1"
                                    keyboardType="number-pad"
                                    maxLength={6}
                                    value={otp}
                                    onChangeText={setOtp}
                                    autoFocus
                                    textContentType="oneTimeCode"
                                    autoComplete="sms-otp"
                                    importantForAutofill="yes"
                                />
                            </View>

                            <TouchableOpacity
                                className="w-full bg-[#ff6b00] py-4 rounded-xl items-center shadow-sm shadow-orange-200"
                                onPress={handleVerifyOtp}
                                disabled={verifying}
                            >
                                {verifying ? (
                                    <ActivityIndicator color="white" />
                                ) : (
                                    <Text className="text-white font-bold text-lg">Verify & Login</Text>
                                )}
                            </TouchableOpacity>

                            <TouchableOpacity 
                                className="mt-6 items-center flex-row justify-center py-2"
                                onPress={() => setShowOtpScreen(false)}
                            >
                                <Ionicons name="arrow-back" size={16} color="#64748b" style={{marginRight: 6}}/>
                                <Text className="text-slate-500 font-medium">Change Mobile Number</Text>
                            </TouchableOpacity>
                        </View>
                    </View>
                )}
            </ScrollView>
        </KeyboardAvoidingView>
    );
}
