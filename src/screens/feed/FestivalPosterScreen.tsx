import React from 'react';
import { View, ActivityIndicator, StyleSheet, TouchableOpacity, Text, SafeAreaView } from 'react-native';
import { WebView } from 'react-native-webview';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import { WEBSITE_URL } from '../../services/api';

const FestivalPosterScreen = () => {
    const navigation = useNavigation<any>();

    // The user said "api ke bahar hi bhai festival.php"
    // API_BASE_URL is 'https://www.sadhuvandna.co.in/Api'
    // So root is 'https://www.sadhuvandna.co.in'
    // Target: 'https://www.sadhuvandna.co.in/festival.php'
    const targetUrl = `${WEBSITE_URL}/festival.php`;

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Ionicons name="arrow-back" size={24} color="black" />
                </TouchableOpacity>
                <Text style={styles.title}>Make Festival Poster</Text>
            </View>
            <WebView
                source={{ uri: targetUrl }}
                startInLoadingState={true}
                renderLoading={() => (
                    <View style={styles.loader}>
                        <ActivityIndicator size="large" color="#ea580c" />
                    </View>
                )}
                style={{ flex: 1 }}
            />
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: 'white' },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: 15,
        borderBottomWidth: 1,
        borderBottomColor: '#eee',
        backgroundColor: 'white'
    },
    backBtn: { marginRight: 15 },
    title: { fontSize: 18, fontWeight: 'bold', color: 'black' },
    loader: {
        position: 'absolute',
        top: 0, left: 0, right: 0, bottom: 0,
        justifyContent: 'center', alignItems: 'center',
        backgroundColor: 'white'
    }
});

export default FestivalPosterScreen;
