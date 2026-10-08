import React, { createContext, useState, useContext, useEffect } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Platform } from 'react-native';
import * as Font from 'expo-font';

// Static global variable for index.ts text render override to read synchronously
export let globalFontFamily: string | undefined = undefined;

export const getFontForPlatform = (fontName: string) => {
    if (fontName === 'System' || fontName === 'System Default') return undefined;
    const isAndroid = Platform.OS === 'android';
    switch (fontName) {
        case 'Devnagari Noto':
            return 'NotoSansDevanagari';
        case 'Devnagari Rozha':
            return 'RozhaOne';
        case 'Devnagari Yatra':
            return 'YatraOne';
        case 'Serif':
            return isAndroid ? 'serif' : 'Georgia';
        case 'Monospace':
            return isAndroid ? 'monospace' : 'Courier';
        case 'Condensed':
            return isAndroid ? 'sans-serif-condensed' : 'Arial';
        case 'Light':
            return isAndroid ? 'sans-serif-light' : 'Helvetica-Light';
        default:
            return undefined;
    }
};

interface FontContextType {
    fontFamily: string;
    setFontFamily: (font: string) => Promise<void>;
}

const FontContext = createContext<FontContextType | undefined>(undefined);

export const FontProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [fontFamily, setFontFamilyState] = useState<string>('System');

    useEffect(() => {
        // Load custom fonts and load current selection
        const init = async () => {
            await loadCustomFonts();
            await loadFontFamily();
        };
        init();
    }, []);

    const loadCustomFonts = async () => {
        try {
            await Font.loadAsync({
                'NotoSansDevanagari': 'https://cdn.jsdelivr.net/gh/google/fonts@main/ofl/notosansdevanagari/NotoSansDevanagari%5Bwdth,wght%5D.ttf',
                'RozhaOne': 'https://cdn.jsdelivr.net/gh/google/fonts@main/ofl/rozhaone/RozhaOne-Regular.ttf',
                'YatraOne': 'https://cdn.jsdelivr.net/gh/google/fonts@main/ofl/yatraone/YatraOne-Regular.ttf'
            });
            console.log("[FontProvider] Devanagari fonts loaded successfully from CDN!");
        } catch (error) {
            console.error("[FontProvider] Failed to load custom fonts from CDN", error);
        }
    };

    const loadFontFamily = async () => {
        try {
            const storedFont = await AsyncStorage.getItem('appFontFamily');
            if (storedFont) {
                setFontFamilyState(storedFont);
                const resolved = getFontForPlatform(storedFont);
                globalFontFamily = resolved;
                (global as any).activeFontFamily = resolved;
                if (typeof (global as any).notifyFontChange === 'function') {
                    (global as any).notifyFontChange(resolved);
                }
            } else {
                globalFontFamily = undefined;
                (global as any).activeFontFamily = undefined;
                if (typeof (global as any).notifyFontChange === 'function') {
                    (global as any).notifyFontChange(undefined);
                }
            }
        } catch (error) {
            console.error("[FontProvider] Failed to load font family from storage", error);
        }
    };

    const setFontFamily = async (font: string) => {
        setFontFamilyState(font);
        const resolved = getFontForPlatform(font);
        globalFontFamily = resolved;
        (global as any).activeFontFamily = resolved;
        await AsyncStorage.setItem('appFontFamily', font);
        if (typeof (global as any).notifyFontChange === 'function') {
            (global as any).notifyFontChange(resolved);
        }
    };

    return (
        <FontContext.Provider value={{ fontFamily, setFontFamily }}>
            {children}
        </FontContext.Provider>
    );
};

export const useFont = () => {
    const context = useContext(FontContext);
    if (!context) {
        throw new Error('useFont must be used within a FontProvider');
    }
    return context;
};
