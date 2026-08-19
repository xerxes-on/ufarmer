import { createContext, useContext, useState } from 'react';

declare global {
    interface Window {
        __UFARM_LANGUAGE_SHIM?: boolean;
    }
}

if (typeof window !== 'undefined') {
    window.__UFARM_LANGUAGE_SHIM = true;
}

export type Language = 'en' | 'uz' | 'ru';

export const translations = {
    en: {
        title: 'UFarmer Offers & Terms',
        subtitle: 'Modern agricultural software platform',
        language: 'Language',
        theme: 'Theme',
        light: 'Light',
        dark: 'Dark',
        loading: 'Loading document...',
        error: 'Error loading document',
        switchLanguage: 'Switch Language',
        toggleTheme: 'Toggle Theme',
    },
    uz: {
        title: 'UFarmer Takliflar va Shartlar',
        subtitle: "Zamonaviy qishloq xo'jaligi dasturiy platformasi",
        language: 'Til',
        theme: 'Mavzu',
        light: 'Yorqin',
        dark: "Qorong'u",
        loading: 'Hujjat yuklanmoqda...',
        error: 'Hujjatni yuklashda xatolik',
        switchLanguage: "Tilni o'zgartirish",
        toggleTheme: 'Mavzuni o\'zgartirish',
    },
    ru: {
        title: 'UFarmer Predlozheniya i Usloviya',
        subtitle: 'Sovremennaya platforma dlya selskogo khozyaystva',
        language: 'Yazyk',
        theme: 'Tema',
        light: 'Svetlaya',
        dark: 'Temnaya',
        loading: 'Zagruzka dokumenta...',
        error: 'Oshibka zagruzki dokumenta',
        switchLanguage: 'Smenit yazyk',
        toggleTheme: 'Pereklyuchit temu',
    },
};

type LanguageContextType = {
    language: Language;
    setLanguage: (lang: Language) => void;
    t: (key: keyof typeof translations.en) => string;
};

export const LanguageContext = createContext<LanguageContextType | undefined>(undefined);

export const useLanguage = () => {
    const context = useContext(LanguageContext);
    if (!context) {
        throw new Error('useLanguage must be used within a LanguageProvider');
    }
    return context;
};

export const useLanguageProvider = () => {
    const [language, setLanguage] = useState<Language>('uz');

    const t = (key: keyof typeof translations.en): string => {
        return translations[language][key] || translations.en[key];
    };

    return { language, setLanguage, t };
};
