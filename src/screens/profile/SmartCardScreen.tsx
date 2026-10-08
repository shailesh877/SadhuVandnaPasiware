import React, { useEffect, useState, useRef } from 'react';
import { View, Text, TouchableOpacity, Image, StyleSheet, ActivityIndicator, Alert, Dimensions, ScrollView } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import ViewShot from 'react-native-view-shot';
import * as Sharing from 'expo-sharing';
import { WEBSITE_URL } from '../../services/api';

const { width } = Dimensions.get('window');

// Standard ATM/Credit card ratio: 85.6mm × 53.98mm ≈ 1.586 : 1
const CARD_WIDTH = width * 0.92;
const CARD_HEIGHT = CARD_WIDTH / 1.586;

const LOGO_URL = `${WEBSITE_URL}/images/logo.png`;
const PHOTO_URL = `${WEBSITE_URL}/uploads/photo/`;

// Format number as card number blocks: SV-00087 → displayed as membership number
const formatCardId = (id: any) => {
    const num = String(id || '0').padStart(16, '0');
    return `${num.slice(0, 4)} ${num.slice(4, 8)} ${num.slice(8, 12)} ${num.slice(12, 16)}`;
};

const SmartCardScreen = ({ navigation }: any) => {
    const [user, setUser] = useState<any>(null);
    const [loading, setLoading] = useState(true);
    const viewShotRef = useRef<ViewShot>(null);

    useEffect(() => {
        loadUser();
    }, []);

    const loadUser = async () => {
        try {
            const uStr = await AsyncStorage.getItem('user');
            if (uStr) {
                setUser(JSON.parse(uStr));
            }
        } catch (e) {
            console.error(e);
        } finally {
            setLoading(false);
        }
    };

    const handleShare = async () => {
        if (!viewShotRef.current?.capture) return;
        try {
            const uri = await viewShotRef.current.capture();
            if (await Sharing.isAvailableAsync()) {
                await Sharing.shareAsync(uri, {
                    mimeType: 'image/png',
                    dialogTitle: 'Share Smart Card',
                });
            } else {
                Alert.alert("Error", "Sharing is not available on this device.");
            }
        } catch (error) {
            console.error("Snapshot failed", error);
            Alert.alert("Error", "Failed to generate smart card image.");
        }
    };

    if (loading) {
        return (
            <View style={{ flex: 1, backgroundColor: '#0a0a0a', justifyContent: 'center', alignItems: 'center' }}>
                <ActivityIndicator size="large" color="#facc15" />
            </View>
        );
    }

    if (!user) {
        return (
            <View style={{ flex: 1, backgroundColor: '#0a0a0a', justifyContent: 'center', alignItems: 'center' }}>
                <Text style={{ color: '#fff' }}>Failed to load user data.</Text>
            </View>
        );
    }

    const cardIdNum = `SV-${String(user.id || '0').padStart(5, '0')}`;
    const formattedNumber = `•••• •••• •••• ${String(user.id || '0').padStart(4, '0')}`;
    const memberSince = new Date().getFullYear();

    return (
        <SafeAreaView style={{ flex: 1, backgroundColor: '#0a0a0a' }}>
            {/* Header */}
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={{ padding: 8 }}>
                    <Ionicons name="arrow-back" size={24} color="#facc15" />
                </TouchableOpacity>
                <Text style={styles.headerTitle}>SMART CARD</Text>
                <View style={{ width: 40 }} />
            </View>

            <ScrollView contentContainerStyle={{ alignItems: 'center', paddingTop: 32, paddingBottom: 50 }}>

                {/* ── ATM CARD ── */}
                <ViewShot ref={viewShotRef} options={{ format: 'png', quality: 1.0 }}
                    style={{ width: CARD_WIDTH, height: CARD_HEIGHT }}>
                    <View style={[styles.card, { width: CARD_WIDTH, height: CARD_HEIGHT }]}>

                        {/* Dark gradient overlay layers */}
                        <View style={styles.gradientTop} />
                        <View style={styles.gradientBall} />

                        {/* TOP ROW: Logo + VIP badge */}
                        <View style={styles.topRow}>
                            <View style={styles.logoBox}>
                                <Image source={{ uri: LOGO_URL }} style={styles.logo} resizeMode="contain" />
                                <View style={{ marginLeft: 8 }}>
                                    <Text style={styles.orgName}>LINKUP</Text>
                                    <Text style={styles.orgSub}>SAMAJ</Text>
                                </View>
                            </View>
                            <View style={styles.vipPill}>
                                <Text style={styles.vipText}>VIP MEMBER</Text>
                            </View>
                        </View>

                        {/* CHIP + CONTACTLESS ROW */}
                        <View style={styles.chipRow}>
                            {/* EMV Chip */}
                            <View style={styles.chip}>
                                <View style={styles.chipInner}>
                                    <View style={styles.chipLine} />
                                    <View style={styles.chipLineH} />
                                    <View style={styles.chipLine} />
                                </View>
                            </View>
                            {/* Contactless symbol */}
                            <View style={{ marginLeft: 12 }}>
                                <Text style={styles.contactless}>))))</Text>
                            </View>
                            {/* Profile Photo — pushed to right */}
                            <View style={styles.photoWrap}>
                                <Image
                                    source={{ uri: user.profile_photo ? `${PHOTO_URL}${user.profile_photo}` : 'https://via.placeholder.com/80' }}
                                    style={styles.photo}
                                />
                            </View>
                        </View>

                        {/* CARD NUMBER */}
                        <Text style={styles.cardNumber}>{formattedNumber}</Text>

                        {/* BOTTOM ROW: Name + Valid + Membership ID */}
                        <View style={styles.bottomRow}>
                            <View>
                                <Text style={styles.fieldLabel}>CARD HOLDER</Text>
                                <Text style={styles.fieldValue} numberOfLines={1}>
                                    {(user.name || 'MEMBER').toUpperCase()}
                                </Text>
                            </View>
                            <View style={{ marginLeft: 28 }}>
                                <Text style={styles.fieldLabel}>VALID THRU</Text>
                                <Text style={styles.fieldValue}>{String(memberSince).slice(-2)}/{String(memberSince + 5).slice(-2)}</Text>
                            </View>
                            <View style={{ flex: 1, alignItems: 'flex-end' }}>
                                <Text style={styles.fieldLabel}>MEMBER ID</Text>
                                <Text style={styles.memberIdText}>{cardIdNum}</Text>
                            </View>
                        </View>

                        {/* Shine overlay */}
                        <View style={styles.shine} />
                    </View>
                </ViewShot>

                {/* BACK OF CARD HINT */}
                <Text style={styles.hint}>Tap the card to share your VIP membership</Text>

                {/* DETAILS CARD */}
                <View style={styles.detailCard}>
                    <DetailRow icon="call" label="Mobile" value={`+91 ${user.phone || user.mobile || 'N/A'}`} />
                    {(user.city || user.address) && (
                        <DetailRow icon="location" label="City" value={user.city || user.address} />
                    )}
                    <DetailRow icon="pricetag" label="Membership No." value={cardIdNum} />
                </View>

                {/* Share Button */}
                <TouchableOpacity onPress={handleShare} style={styles.shareBtn}>
                    <Ionicons name="cloud-download" size={22} color="#000" />
                    <Text style={styles.shareBtnText}>DOWNLOAD CARD</Text>
                </TouchableOpacity>
            </ScrollView>
        </SafeAreaView>
    );
};

const DetailRow = ({ icon, label, value }: any) => (
    <View style={{ flexDirection: 'row', alignItems: 'center', paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#1e293b' }}>
        <View style={{ width: 34, height: 34, borderRadius: 17, backgroundColor: '#1e293b', alignItems: 'center', justifyContent: 'center', marginRight: 14 }}>
            <Ionicons name={icon} size={16} color="#fbbf24" />
        </View>
        <View style={{ flex: 1 }}>
            <Text style={{ fontSize: 10, color: '#64748b', fontWeight: '700', letterSpacing: 1, textTransform: 'uppercase' }}>{label}</Text>
            <Text style={{ fontSize: 15, color: '#f8fafc', fontWeight: '700', marginTop: 2 }}>{value}</Text>
        </View>
    </View>
);

const styles = StyleSheet.create({
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: 16,
        paddingVertical: 14,
        backgroundColor: '#0a0a0a',
        borderBottomWidth: 1,
        borderBottomColor: '#1a1a1a',
    },
    headerTitle: {
        color: '#fff',
        fontSize: 18,
        fontWeight: '900',
        letterSpacing: 4,
    },

    // ── Card Styles ──
    card: {
        backgroundColor: '#0f172a',
        borderRadius: 18,
        padding: 20,
        overflow: 'hidden',
        // Gold shadow glow
        elevation: 24,
        shadowColor: '#fbbf24',
        shadowOffset: { width: 0, height: 8 },
        shadowOpacity: 0.35,
        shadowRadius: 20,
        borderWidth: 1,
        borderColor: '#2d3748',
        justifyContent: 'space-between',
    },
    gradientTop: {
        position: 'absolute',
        top: -40,
        right: -40,
        width: CARD_WIDTH * 0.55,
        height: CARD_WIDTH * 0.55,
        borderRadius: CARD_WIDTH * 0.275,
        backgroundColor: 'rgba(251,191,36,0.07)',
    },
    gradientBall: {
        position: 'absolute',
        bottom: -30,
        left: -30,
        width: CARD_WIDTH * 0.4,
        height: CARD_WIDTH * 0.4,
        borderRadius: CARD_WIDTH * 0.2,
        backgroundColor: 'rgba(234,88,12,0.08)',
    },
    shine: {
        position: 'absolute',
        top: 0,
        left: 0,
        right: 0,
        height: CARD_HEIGHT * 0.4,
        backgroundColor: 'rgba(255,255,255,0.02)',
        borderBottomLeftRadius: 80,
        borderBottomRightRadius: 80,
    },

    // Top row
    topRow: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
    },
    logoBox: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    logo: {
        width: 32,
        height: 32,
    },
    orgName: {
        color: '#fcd34d',
        fontSize: 11,
        fontWeight: '900',
        letterSpacing: 1,
    },
    orgSub: {
        color: '#94a3b8',
        fontSize: 9,
        fontWeight: '700',
        letterSpacing: 2,
    },
    vipPill: {
        backgroundColor: 'rgba(251,191,36,0.15)',
        borderWidth: 1,
        borderColor: '#fbbf24',
        borderRadius: 20,
        paddingHorizontal: 10,
        paddingVertical: 3,
    },
    vipText: {
        color: '#fbbf24',
        fontSize: 9,
        fontWeight: '900',
        letterSpacing: 1.5,
    },

    // Chip row
    chipRow: {
        flexDirection: 'row',
        alignItems: 'center',
        marginTop: 2,
    },
    chip: {
        width: 36,
        height: 28,
        backgroundColor: '#d4af37',
        borderRadius: 5,
        justifyContent: 'center',
        alignItems: 'center',
        elevation: 3,
    },
    chipInner: {
        width: 28,
        height: 20,
        borderWidth: 0.5,
        borderColor: '#b8860b',
        borderRadius: 3,
        justifyContent: 'space-around',
        alignItems: 'center',
        paddingVertical: 2,
    },
    chipLine: {
        width: 24,
        height: 1.5,
        backgroundColor: '#b8860b',
    },
    chipLineH: {
        width: 1.5,
        height: 14,
        backgroundColor: '#b8860b',
        position: 'absolute',
    },
    contactless: {
        color: '#94a3b8',
        fontSize: 14,
        letterSpacing: -4,
        fontWeight: '300',
        transform: [{ rotate: '90deg' }],
    },
    photoWrap: {
        marginLeft: 'auto' as any,
        width: CARD_HEIGHT * 0.38,
        height: CARD_HEIGHT * 0.38,
        borderRadius: CARD_HEIGHT * 0.19,
        borderWidth: 2,
        borderColor: '#fbbf24',
        overflow: 'hidden',
    },
    photo: {
        width: '100%',
        height: '100%',
    },

    // Card number
    cardNumber: {
        color: '#e2e8f0',
        fontSize: 16,
        fontWeight: '700',
        letterSpacing: 3,
        fontVariant: ['tabular-nums'],
    },

    // Bottom row
    bottomRow: {
        flexDirection: 'row',
        alignItems: 'flex-end',
    },
    fieldLabel: {
        color: '#64748b',
        fontSize: 8,
        fontWeight: '800',
        letterSpacing: 1.5,
        marginBottom: 3,
        textTransform: 'uppercase',
    },
    fieldValue: {
        color: '#f8fafc',
        fontSize: 13,
        fontWeight: '800',
        letterSpacing: 0.5,
        maxWidth: 120,
    },
    memberIdText: {
        color: '#fbbf24',
        fontSize: 13,
        fontWeight: '900',
        letterSpacing: 2,
    },

    // Below card
    hint: {
        color: '#475569',
        fontSize: 11,
        marginTop: 16,
        marginBottom: 28,
        letterSpacing: 0.5,
        textAlign: 'center',
    },
    detailCard: {
        width: CARD_WIDTH,
        backgroundColor: '#111827',
        borderRadius: 16,
        paddingHorizontal: 20,
        paddingVertical: 4,
        borderWidth: 1,
        borderColor: '#1e293b',
        marginBottom: 28,
    },
    shareBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#facc15',
        borderRadius: 50,
        paddingHorizontal: 40,
        paddingVertical: 16,
        elevation: 6,
        shadowColor: '#facc15',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.4,
        shadowRadius: 10,
    },
    shareBtnText: {
        color: '#000',
        fontWeight: '900',
        fontSize: 16,
        marginLeft: 10,
        letterSpacing: 2,
    },
});

export default SmartCardScreen;
