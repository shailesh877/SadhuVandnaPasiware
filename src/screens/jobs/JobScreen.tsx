import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, TouchableOpacity, ActivityIndicator, Linking, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api from '../../services/api';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';

const JobScreen = ({ navigation }: any) => {
    const [jobs, setJobs] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchJobs();
    }, []);

    const fetchJobs = async () => {
        try {
            const res = await api.get('/get_jobs.php');
            if (res.data.status === 'success') {
                setJobs(res.data.data);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const renderItem = ({ item }: { item: any }) => (
        <JobCard item={item} />
    );

    if (loading) {
        return <View className="flex-1 justify-center items-center bg-white"><ActivityIndicator color="#ea580c" /></View>;
    }

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            <View className="flex-row items-center p-4 border-b border-gray-100">
                <TouchableOpacity onPress={() => navigation.goBack()} className="mr-3">
                    <Ionicons name="arrow-back" size={24} color="black" />
                </TouchableOpacity>
                <Text className="text-xl font-bold text-gray-800">Jobs & Education</Text>
            </View>

            <FlatList
                data={jobs}
                renderItem={renderItem}
                keyExtractor={item => item.id?.toString()}
                contentContainerStyle={{ paddingVertical: 10 }}
                ListEmptyComponent={
                    <View className="items-center mt-20 p-4">
                        <Text className="text-gray-500 text-center text-lg">No updates available.</Text>
                    </View>
                }
            />
        </SafeAreaView>
    );
};

const JobCard = ({ item }: { item: any }) => {
    const navigation = useNavigation<any>();
    const [expanded, setExpanded] = useState(false);
    const description = item.description || '';
    const isLong = description.length > 150;

    const handleApply = () => {
        navigation.navigate('ApplyJob', { jobId: item.id, jobTitle: item.title });
    };

    return (
        <View className="bg-white mb-4 mx-4 p-5 rounded-xl border border-gray-100 shadow-sm">
            <View className="flex-row justify-between items-start mb-2">
                <View className="flex-1">
                    <Text className={`text-xs font-bold px-2 py-1 rounded self-start mb-2 ${item.type === 'job' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'}`}>
                        {item.type ? item.type.toUpperCase() : 'UPDATE'}
                    </Text>
                    <Text className="text-lg font-bold text-gray-900 leading-6">{item.title}</Text>
                </View>
                <Text className="text-xs text-gray-400">{new Date(item.created_at).toLocaleDateString()}</Text>
            </View>

            <Text className="text-gray-600 text-sm leading-5 mb-2">
                {expanded ? description : description.substring(0, 150) + (isLong ? '...' : '')}
            </Text>

            {isLong && (
                <TouchableOpacity onPress={() => setExpanded(!expanded)} className="mb-4">
                    <Text className="text-orange-600 font-bold text-xs">{expanded ? 'Read Less' : 'Read More'}</Text>
                </TouchableOpacity>
            )}

            {item.type === 'job' && (
                <TouchableOpacity
                    className="bg-orange-600 py-2.5 px-4 rounded-lg self-start flex-row items-center space-x-2 mt-2"
                    onPress={handleApply}
                >
                    <Text className="text-white font-bold text-sm">Apply Now</Text>
                </TouchableOpacity>
            )}
        </View>
    );
};

export default JobScreen;
