import React from 'react';
import { View, StyleSheet } from 'react-native';

export interface FilterConfig {
    id: string;
    name: string;
    color?: string;
    vignette?: boolean;
    grain?: boolean;
    dualTone?: [string, string];
}

export const FILTERS: FilterConfig[] = [
    { id: 'none', name: 'Original', color: 'transparent' },
    { id: 'cinematic', name: 'Cinematic', color: 'rgba(50, 20, 0, 0.15)', vignette: true },
    { id: 'bw_grain', name: 'Vintage', color: 'rgba(0,0,0,0.4)', grain: true },
    { id: 'cyberpunk', name: 'Cyber', dualTone: ['rgba(255,0,255,0.15)', 'rgba(0,255,255,0.1)'] },
    { id: 'sunset', name: 'Sunset', color: 'rgba(255, 69, 0, 0.15)', vignette: true },
    { id: 'cool_night', name: 'Cool', color: 'rgba(0, 0, 139, 0.1)', dualTone: ['rgba(0,0,50,0.2)', 'rgba(0,100,255,0.05)'] },
    { id: 'retro_sepia', name: 'Retro', color: 'rgba(112, 66, 20, 0.25)', grain: true, vignette: true },
];

export const VignetteOverlay: React.FC = () => {
    return (
        <View style={StyleSheet.absoluteFill} pointerEvents="none">
            <View style={[styles.vignettePart, { top: -20, left: -20, right: -20, height: 150, borderBottomLeftRadius: 100, borderBottomRightRadius: 100 }]} />
            <View style={[styles.vignettePart, { bottom: -20, left: -20, right: -20, height: 150, borderTopLeftRadius: 100, borderTopRightRadius: 100 }]} />
            <View style={[styles.vignettePart, { left: -20, top: 0, bottom: 0, width: 80, borderTopRightRadius: 50, borderBottomRightRadius: 50 }]} />
            <View style={[styles.vignettePart, { right: -20, top: 0, bottom: 0, width: 80, borderTopLeftRadius: 50, borderBottomLeftRadius: 50 }]} />
        </View>
    );
};

export const FilmGrain: React.FC = () => {
    return (
        <View style={[StyleSheet.absoluteFill, { backgroundColor: 'rgba(255,255,255,0.03)' }]} pointerEvents="none">
            <View style={[StyleSheet.absoluteFill, { opacity: 0.1, backgroundColor: '#000' }]} />
        </View>
    );
};

const styles = StyleSheet.create({
    vignettePart: {
        position: 'absolute',
        backgroundColor: 'rgba(0,0,0,0.5)',
        shadowColor: 'black',
        shadowOffset: { width: 0, height: 0 },
        shadowOpacity: 1,
        shadowRadius: 50,
        elevation: 10,
    },
});
