import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, ScrollView, Alert, ActivityIndicator, Image } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation, useRoute } from '@react-navigation/native';
import * as DocumentPicker from 'expo-document-picker';
import api from '../../services/api';

const ApplyJobScreen = () => {
    const navigation = useNavigation();
    const route = useRoute<any>();
    const { jobId, jobTitle } = route.params || {};

    const [name, setName] = useState('');
    const [phone, setPhone] = useState('');
    const [email, setEmail] = useState('');
    const [education, setEducation] = useState('');

    const [photo, setPhoto] = useState<any>(null);
    const [aadhaar, setAadhaar] = useState<any>(null);
    const [resume, setResume] = useState<any>(null);

    const [loading, setLoading] = useState(false);

    const pickDocument = async (type: 'photo' | 'aadhaar' | 'resume') => {
        try {
            const result = await DocumentPicker.getDocumentAsync({
                type: type === 'photo' ? 'image/*' : (type === 'resume' ? ['application/pdf', 'application/msword'] : '*/*'),
                copyToCacheDirectory: true,
            });

            if (!result.canceled && result.assets && result.assets.length > 0) {
                const file = result.assets[0];
                if (type === 'photo') setPhoto(file);
                else if (type === 'aadhaar') setAadhaar(file);
                else if (type === 'resume') setResume(file);
            }
        } catch (err) {
            console.warn(err);
        }
    };

    const handleSubmit = async () => {
        if (!name || !phone || !email || !education || !photo || !aadhaar || !resume) {
            Alert.alert("Missing Fields", "Please fill all fields and upload all documents.");
            return;
        }

        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('job_id', jobId);
            formData.append('name', name);
            formData.append('phone', phone);
            formData.append('email', email);
            formData.append('education', education);

            const appendFile = (key: string, file: any) => {
                formData.append(key, {
                    uri: file.uri,
                    name: file.name,
                    type: file.mimeType || 'application/octet-stream'
                } as any);
            };

            appendFile('photo', photo);
            appendFile('aadhaar', aadhaar);
            appendFile('resume', resume);

            const res = await api.post('/submit_job_application.php', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            if (res.data.status === 'success') {
                Alert.alert("Success", "Application Submitted Successfully!", [
                    { text: "OK", onPress: () => navigation.goBack() }
                ]);
            } else {
                Alert.alert("Error", res.data.message || "Failed to submit application.");
            }
        } catch (error) {
            console.error(error);
            Alert.alert("Error", "Something went wrong. Please try again.");
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
                <Text style={{ fontSize: 20, fontWeight: 'bold', marginLeft: 16 }}>Apply for Job</Text>
            </View>

            <ScrollView contentContainerStyle={{ padding: 20 }}>
                <Text style={{ fontSize: 18, fontWeight: 'bold', color: '#ea580c', marginBottom: 20 }}>{jobTitle}</Text>

                <View style={{ gap: 16 }}>
                    <View>
                        <Text style={{ fontWeight: '600', marginBottom: 6 }}>Full Name</Text>
                        <TextInput
                            style={{ borderWidth: 1, borderColor: '#d1d5db', borderRadius: 8, padding: 12 }}
                            placeholder="Enter your name"
                            value={name} onChangeText={setName}
                        />
                    </View>

                    <View>
                        <Text style={{ fontWeight: '600', marginBottom: 6 }}>Phone Number</Text>
                        <TextInput
                            style={{ borderWidth: 1, borderColor: '#d1d5db', borderRadius: 8, padding: 12 }}
                            placeholder="Enter phone number"
                            keyboardType="phone-pad"
                            value={phone} onChangeText={setPhone}
                        />
                    </View>

                    <View>
                        <Text style={{ fontWeight: '600', marginBottom: 6 }}>Email Address</Text>
                        <TextInput
                            style={{ borderWidth: 1, borderColor: '#d1d5db', borderRadius: 8, padding: 12 }}
                            placeholder="Enter email"
                            keyboardType="email-address"
                            value={email} onChangeText={setEmail}
                            autoCapitalize="none"
                        />
                    </View>

                    <View>
                        <Text style={{ fontWeight: '600', marginBottom: 6 }}>Education / Qualification</Text>
                        <TextInput
                            style={{ borderWidth: 1, borderColor: '#d1d5db', borderRadius: 8, padding: 12 }}
                            placeholder="e.g. B.Tech, MBA"
                            value={education} onChangeText={setEducation}
                        />
                    </View>

                    {/* File Uploads */}
                    {[
                        { label: 'Photo', state: photo, type: 'photo' },
                        { label: 'Aadhaar Card', state: aadhaar, type: 'aadhaar' },
                        { label: 'Resume (PDF/Doc)', state: resume, type: 'resume' }
                    ].map((field: any) => (
                        <View key={field.type}>
                            <Text style={{ fontWeight: '600', marginBottom: 6 }}>{field.label}</Text>
                            <TouchableOpacity
                                onPress={() => pickDocument(field.type)}
                                style={{
                                    borderWidth: 1, borderColor: field.state ? '#ea580c' : '#d1d5db',
                                    borderStyle: 'dashed', borderRadius: 8, padding: 16,
                                    alignItems: 'center', backgroundColor: field.state ? '#fff7ed' : '#f9fafb'
                                }}
                            >
                                <Ionicons name={field.state ? "checkmark-circle" : "cloud-upload-outline"} size={24} color={field.state ? "#ea580c" : "#9ca3af"} />
                                <Text style={{ color: field.state ? '#c2410c' : '#6b7280', marginTop: 4 }}>
                                    {field.state ? field.state.name : "Tap to Upload"}
                                </Text>
                            </TouchableOpacity>
                        </View>
                    ))}

                    <TouchableOpacity
                        onPress={handleSubmit}
                        disabled={loading}
                        style={{ backgroundColor: '#ea580c', padding: 16, borderRadius: 12, alignItems: 'center', marginTop: 10, opacity: loading ? 0.7 : 1 }}
                    >
                        {loading ? <ActivityIndicator color="white" /> : (
                            <Text style={{ color: 'white', fontWeight: 'bold', fontSize: 16 }}>Submit Application</Text>
                        )}
                    </TouchableOpacity>

                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

export default ApplyJobScreen;
