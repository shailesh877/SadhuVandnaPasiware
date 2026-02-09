import React from 'react';
import { View, Text, Image, ScrollView, TouchableOpacity, Alert, Linking, ActivityIndicator } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRoute, useNavigation } from '@react-navigation/native';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const MarriageDetailScreen = () => {
    const route = useRoute<any>();
    const navigation = useNavigation<any>();
    const { profile } = route.params;

    const [currentUserId, setCurrentUserId] = React.useState<string | null>(null);
    const [proposalStatus, setProposalStatus] = React.useState<string>('none');
    const [loading, setLoading] = React.useState(false);

    React.useEffect(() => {
        loadUser();
    }, []);

    React.useEffect(() => {
        if (currentUserId && profile.id) {
            checkStatus();
        }
    }, [currentUserId, profile]);

    const loadUser = async () => {
        const uStr = await AsyncStorage.getItem('user');
        if (uStr) {
            setCurrentUserId(JSON.parse(uStr).id);
        }
    };

    const checkStatus = async () => {
        if (!currentUserId) return;
        try {
            const res = await api.get(`/get_proposal_status.php?user_id=${currentUserId}&receiver_id=${profile.id}`);
            if (res.data.status === 'success') {
                setProposalStatus(res.data.proposal_status);
            }
        } catch (error) {
            console.error(error);
        }
    };

    const checkPaymentAndNavigate = async () => {
        if (!currentUserId) return;

        try {
            const fd = new FormData();
            fd.append('user_id', currentUserId);
            fd.append('receiver_id', profile.id);

            const res = await api.post('/check_chat_payment.php', fd);

            if (res.data.status === 'success') {
                if (res.data.paid) {
                    navigation.navigate('Chat', { receiver: { id: profile.id, name: profile.full_name, profile_photo: profile.photo } });
                } else {
                    const paymentUrl = `${API_BASE_URL.replace('/Api', '')}/${res.data.payment_url}`;
                    Alert.alert(
                        "Payment Required",
                        "You need to pay to chat with this profile.",
                        [
                            { text: "Cancel", style: "cancel" },
                            { text: "Pay Now", onPress: () => Linking.openURL(paymentUrl) }
                        ]
                    );
                }
            } else {
                Alert.alert("Error", res.data.message || "Failed to check payment status");
            }
        } catch (error) {
            console.error(error);
            Alert.alert("Error", "Network request failed");
        }
    };

    const handleSendProposal = async () => {
        if (!currentUserId) {
            Alert.alert("Error", "Please login first");
            return;
        }

        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('user_id', currentUserId);
            formData.append('receiver_id', profile.id);

            const res = await api.post('/send_proposal.php', formData);
            if (res.data.status === 'success') {
                Alert.alert("Success", "Proposal Sent Successfully!");
                setProposalStatus('pending');
            } else {
                Alert.alert("Notice", res.data.message);
                if (res.data.message.includes("already sent")) {
                    setProposalStatus('pending');
                }
            }
        } catch (error) {
            Alert.alert("Error", "Network request failed");
        } finally {
            setLoading(false);
        }
    };

    const SectionHeader = ({ title, icon }: any) => (
        <View className="flex-row items-center gap-2 mt-6 mb-3 bg-orange-50 p-2 rounded-lg">
            <Ionicons name={icon} size={20} color="#ea580c" />
            <Text className="text-lg font-bold text-orange-800">{title}</Text>
        </View>
    );

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            <ScrollView showsVerticalScrollIndicator={false}>
                <View className="relative">
                    <Image
                        source={{ uri: profile.photo ? `${PHOTO_URL}${profile.photo}` : 'https://via.placeholder.com/300' }}
                        className="w-full h-96 bg-gray-200"
                        resizeMode="cover"
                    />
                    <TouchableOpacity
                        onPress={() => navigation.goBack()}
                        className="absolute top-4 left-4 bg-black/30 p-2 rounded-full"
                    >
                        <Ionicons name="arrow-back" size={24} color="white" />
                    </TouchableOpacity>
                </View>

                <View className="p-6 -mt-10 bg-white rounded-t-3xl shadow-lg min-h-screen">
                    <View className="flex-row justify-between items-start">
                        <View className="flex-1">
                            <Text className="text-2xl font-bold text-gray-900">{profile.full_name}</Text>
                            <Text className="text-orange-600 font-medium text-base mb-1">
                                {profile.occupation} {profile.work_place ? `at ${profile.work_place}` : ''}
                            </Text>
                            <Text className="text-gray-500 text-sm">
                                <Ionicons name="location" size={12} /> {profile.city}
                            </Text>
                        </View>
                        <View className="bg-orange-100 px-3 py-1 rounded-full border border-orange-200">
                            {/* Calculate Age roughly */}
                            <Text className="text-orange-800 font-bold">{new Date().getFullYear() - new Date(profile.dob).getFullYear()} yrs</Text>
                        </View>
                    </View>

                    {/* Action Buttons */}
                    <View className="mt-6 mb-2">
                        {proposalStatus === 'accepted' || proposalStatus === 'friend' ? (
                            <TouchableOpacity
                                className="bg-green-600 p-4 rounded-xl items-center shadow-lg shadow-green-100 flex-row justify-center gap-2"
                                onPress={checkPaymentAndNavigate}
                            >
                                <Ionicons name="chatbubbles" size={20} color="white" />
                                <Text className="text-white font-bold text-lg">Send Message</Text>
                            </TouchableOpacity>
                        ) : (
                            <TouchableOpacity
                                className={`p-4 rounded-xl items-center shadow-lg flex-row justify-center gap-2 ${proposalStatus === 'pending' ? 'bg-gray-200' : 'bg-orange-600 shadow-orange-200'}`}
                                onPress={handleSendProposal}
                                disabled={proposalStatus === 'pending' || loading}
                            >
                                {loading ? <ActivityIndicator color="white" /> : (
                                    <>
                                        <Ionicons name={proposalStatus === 'pending' ? "time" : "heart"} size={20} color={proposalStatus === 'pending' ? "gray" : "white"} />
                                        <Text className={`font-bold text-lg ${proposalStatus === 'pending' ? 'text-gray-500' : 'text-white'}`}>
                                            {proposalStatus === 'pending' ? "Request Sent" : "Send Interest"}
                                        </Text>
                                    </>
                                )}
                            </TouchableOpacity>
                        )}
                    </View>

                    <Text className="text-gray-600 italic my-4 leading-5 bg-gray-50 p-3 rounded-lg border border-gray-100 text-center">
                        "{profile.about || "No bio available"}"
                    </Text>

                    {/* Basic Info */}
                    <SectionHeader title="Basic Details" icon="person" />
                    <View className="space-y-3">
                        <InfoRow label="Age / DOB" value={`${profile.dob} (${new Date().getFullYear() - new Date(profile.dob).getFullYear()} yrs)`} />
                        <InfoRow label="Height" value={profile.height} />
                        <InfoRow label="Weight" value={profile.weight} />
                        <InfoRow label="Marital Status" value={profile.status} />
                        <InfoRow label="Religion" value={profile.religion} />
                        <InfoRow label="Caste" value={profile.caste} />
                        <InfoRow label="Kuldevi" value={profile.kuldevi} />
                    </View>

                    {/* Education & Career */}
                    <SectionHeader title="Education & Career" icon="briefcase" />
                    <View className="space-y-3">
                        <InfoRow label="Education" value={profile.education} />
                        <InfoRow label="Occupation" value={profile.occupation} />
                        <InfoRow label="Work Place" value={profile.work_place} />
                        <InfoRow label="Income" value={profile.income} />
                    </View>

                    {/* Family Details */}
                    <SectionHeader title="Family Details" icon="people" />
                    <View className="space-y-3">
                        <InfoRow label="Father" value={`${profile.father_name} (${profile.father_occupation})`} />
                        <InfoRow label="Mother" value={profile.mother_name} />
                        <InfoRow label="Siblings" value={profile.siblings} />
                        <InfoRow label="Family Type" value={profile.family_type} />
                    </View>

                    {/* Lifestyle & Habits */}
                    <SectionHeader title="Lifestyle & Habits" icon="cafe" />
                    <View className="space-y-3">
                        <InfoRow label="Nature" value={profile.nature} />
                        <InfoRow label="Food Habit" value={profile.food} />
                        <InfoRow label="Habits" value={profile.habits} />
                        <InfoRow label="Hobbies" value={profile.hobbies} />
                    </View>

                    {/* Location */}
                    <SectionHeader title="Location" icon="location" />
                    <View className="space-y-3">
                        <InfoRow label="City" value={profile.city} />
                        <InfoRow label="Residence" value={profile.residence} />
                    </View>

                    {/* Partner Preferences */}
                    <SectionHeader title="Partner Preference" icon="heart" />
                    <View className="space-y-3">
                        <InfoRow label="Age Range" value={`${profile.partner_age_from} - ${profile.partner_age_to} yrs`} />
                        <InfoRow label="Education" value={profile.partner_education} />
                        <View className="mt-2">
                            <Text className="text-gray-500 text-xs uppercase mb-1">Expectations</Text>
                            <Text className="text-gray-800 bg-orange-50 p-2 rounded-lg border border-orange-100">{profile.partner_expectations || 'N/A'}</Text>
                        </View>
                    </View>

                    <View className="h-10" />
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const InfoRow = ({ label, value }: { label: string, value: string }) => (
    <View className="flex-row border-b border-gray-100 py-2 items-start">
        <Text className="w-32 text-gray-400 text-sm font-medium">{label}</Text>
        <Text className="flex-1 text-gray-800 text-sm font-semibold">{value || 'N/A'}</Text>
    </View>
);

export default MarriageDetailScreen;
