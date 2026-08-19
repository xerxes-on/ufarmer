import { useState, useEffect, useCallback, useMemo } from 'react';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import { useLanguage } from '@/hooks/useLanguage';

type Language = 'uz' | 'ru' | 'en';

interface DocumentMeta {
    version?: string;
    lastUpdated?: string;
    effectiveDate?: string;
}

interface Translations {
    title: Record<Language, string>;
    description: Record<Language, string>;
    loading: Record<Language, string>;
    error: Record<Language, string>;
    retry: Record<Language, string>;
    tableOfContents: Record<Language, string>;
    backToTop: Record<Language, string>;
    version: Record<Language, string>;
    lastUpdated: Record<Language, string>;
    effectiveDate: Record<Language, string>;
}

const translations: Translations = {
    title: {
        uz: 'Huquqiy Hujjatlar',
        ru: 'Юридические Документы',
        en: 'Legal Documents',
    },
    description: {
        uz: 'UFarm foydalanish shartlari va maxfiylik siyosati',
        ru: 'Условия использования и политика конфиденциальности UFarm',
        en: 'UFarm Terms of Service and Privacy Policy',
    },
    loading: {
        uz: 'Yuklanmoqda...',
        ru: 'Загрузка...',
        en: 'Loading...',
    },
    error: {
        uz: 'Xatolik yuz berdi',
        ru: 'Произошла ошибка',
        en: 'An error occurred',
    },
    retry: {
        uz: 'Qayta urinish',
        ru: 'Повторить',
        en: 'Retry',
    },
    tableOfContents: {
        uz: 'Mundarija',
        ru: 'Содержание',
        en: 'Contents',
    },
    backToTop: {
        uz: 'Yuqoriga',
        ru: 'Наверх',
        en: 'Back to top',
    },
    version: {
        uz: 'Versiya',
        ru: 'Версия',
        en: 'Version',
    },
    lastUpdated: {
        uz: 'Yangilangan',
        ru: 'Обновлено',
        en: 'Updated',
    },
    effectiveDate: {
        uz: "Kuchga kirgan sana",
        ru: 'Дата вступления в силу',
        en: 'Effective Date',
    },
};

interface HeadingItem {
    id: string;
    text: string;
    level: number;
}

function App() {
    const { language, setLanguage, t } = useLanguage();
    const [content, setContent] = useState<string>('');
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [isScrolled, setIsScrolled] = useState(false);
    const [showBackToTop, setShowBackToTop] = useState(false);
    const [activeSection, setActiveSection] = useState<string>('');
    const [meta] = useState<DocumentMeta>({
        version: '2.0',
        lastUpdated: new Date().toLocaleDateString(language === 'uz' ? 'uz-UZ' : language === 'ru' ? 'ru-RU' : 'en-US'),
        effectiveDate: '2024-01-01',
    });

    // Determine document type from URL
    const documentType = useMemo(() => {
        const path = window.location.pathname;
        if (path.includes('offer') || path.includes('oferta')) return 'offer';
        if (path.includes('term') || path.includes('privacy')) return 'term';
        return 'offer';
    }, []);

    // Fetch document content
    const fetchContent = useCallback(async () => {
        setIsLoading(true);
        setError(null);

        try {
            const fileName = documentType === 'offer' ? 'offer' : 'term';
            const response = await fetch(`/offers/${fileName}_${language}.md`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const text = await response.text();
            setContent(text);
        } catch (err) {
            console.error('Failed to fetch content:', err);
            setError(err instanceof Error ? err.message : 'Unknown error');
        } finally {
            setIsLoading(false);
        }
    }, [language, documentType]);

    useEffect(() => {
        fetchContent();
    }, [fetchContent]);

    // Handle scroll events
    useEffect(() => {
        const handleScroll = () => {
            setIsScrolled(window.scrollY > 20);
            setShowBackToTop(window.scrollY > 400);

            // Update active section for TOC
            const headings = document.querySelectorAll('.document-content h1, .document-content h2, .document-content h3');
            let currentActive = '';

            headings.forEach((heading) => {
                const rect = heading.getBoundingClientRect();
                if (rect.top <= 150) {
                    currentActive = heading.id;
                }
            });

            setActiveSection(currentActive);
        };

        window.addEventListener('scroll', handleScroll, { passive: true });
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    // Extract headings for TOC
    const headings = useMemo((): HeadingItem[] => {
        const matches = content.match(/^#{1,3}\s+.+$/gm) || [];
        return matches.map((match, index) => {
            const level = match.match(/^#+/)?.[0].length || 1;
            const text = match.replace(/^#+\s+/, '');
            const id = `heading-${index}-${text.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;
            return { id, text, level };
        });
    }, [content]);

    const scrollToTop = () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const scrollToHeading = (id: string) => {
        const element = document.getElementById(id);
        if (element) {
            const offset = 100;
            const elementPosition = element.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - offset;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth',
            });
        }
    };

    // Custom heading renderer with IDs for TOC
    const HeadingRenderer = ({ level, children }: { level: number; children: React.ReactNode }) => {
        const text = String(children);
        const headingIndex = headings.findIndex((h) => h.text === text);
        const id = headingIndex >= 0 ? headings[headingIndex].id : `heading-${text.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;

        const Tag = `h${level}` as keyof JSX.IntrinsicElements;
        return (
            <Tag id={id} className="scroll-mt-24">
                {children}
            </Tag>
        );
    };

    return (
        <div className="min-h-screen bg-background">
            {/* Animated Background */}
            <div className="fixed inset-0 -z-10 overflow-hidden">
                <div className="mesh-bg absolute inset-0 opacity-60" />
                <div className="absolute top-20 left-10 w-72 h-72 bg-primary/5 rounded-full blur-3xl float" />
                <div className="absolute bottom-20 right-10 w-96 h-96 bg-accent/5 rounded-full blur-3xl float-delayed" />
                <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] morph bg-gradient-to-br from-primary/3 to-accent/3 blur-3xl opacity-30" />
            </div>

            {/* Header */}
            <header className={`app-header ${isScrolled ? 'scrolled' : ''}`}>
                <div className="header-container">
                    <div className="logo-section">
                        <div className="logo-icon hover-scale">
                            <img src="/logo.png" alt="UFarm" className="w-8 h-8" />
                        </div>
                        <div>
                            <h1 className="logo-text">UFarm</h1>
                            <p className="tagline">{t(translations.description)}</p>
                        </div>
                    </div>

                    {/* Language Selector */}
                    <div className="language-selector">
                        {(['uz', 'ru', 'en'] as Language[]).map((lang) => (
                            <button
                                key={lang}
                                onClick={() => setLanguage(lang)}
                                className={`lang-btn ${language === lang ? 'active' : ''}`}
                            >
                                {lang}
                            </button>
                        ))}
                    </div>
                </div>
            </header>

            {/* Main Content */}
            <main className="app-main">
                <div className="document-container">
                    {isLoading ? (
                        <div className="document-card">
                            <div className="loading-container">
                                <div className="loading-spinner" />
                                <p className="loading-text">{t(translations.loading)}</p>
                            </div>
                        </div>
                    ) : error ? (
                        <div className="document-card">
                            <div className="error-container">
                                <svg
                                    className="error-icon"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                                <h2 className="error-title">{t(translations.error)}</h2>
                                <p className="error-message">{error}</p>
                                <button onClick={fetchContent} className="retry-button hover-lift">
                                    {t(translations.retry)}
                                </button>
                            </div>
                        </div>
                    ) : (
                        <article className="document-card">
                            <div className="document-header">
                                <h1 className="document-title">{t(translations.title)}</h1>
                                <div className="document-meta stagger-children">
                                    <span className="meta-item">
                                        <svg className="meta-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                        {t(translations.version)}: {meta.version}
                                    </span>
                                    <span className="meta-item">
                                        <svg className="meta-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {t(translations.lastUpdated)}: {meta.lastUpdated}
                                    </span>
                                </div>
                            </div>
                            <div className="document-content prose prose-lg max-w-none">
                                <ReactMarkdown
                                    remarkPlugins={[remarkGfm]}
                                    components={{
                                        h1: ({ children }) => <HeadingRenderer level={1}>{children}</HeadingRenderer>,
                                        h2: ({ children }) => <HeadingRenderer level={2}>{children}</HeadingRenderer>,
                                        h3: ({ children }) => <HeadingRenderer level={3}>{children}</HeadingRenderer>,
                                    }}
                                >
                                    {content}
                                </ReactMarkdown>
                            </div>
                        </article>
                    )}
                </div>

                {/* Table of Contents - Desktop Only */}
                {!isLoading && !error && headings.length > 0 && (
                    <nav className="toc-sidebar scrollbar-thin">
                        <h2 className="toc-title">{t(translations.tableOfContents)}</h2>
                        <ul className="toc-list">
                            {headings.map((heading) => (
                                <li key={heading.id} className="toc-item">
                                    <button
                                        onClick={() => scrollToHeading(heading.id)}
                                        className={`toc-link level-${heading.level} ${activeSection === heading.id ? 'active' : ''}`}
                                    >
                                        {heading.text}
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </nav>
                )}

                {/* Back to Top Button */}
                <button
                    onClick={scrollToTop}
                    className={`back-to-top ${showBackToTop ? 'visible' : ''}`}
                    aria-label={t(translations.backToTop)}
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="m18 15-6-6-6 6" />
                    </svg>
                </button>
            </main>

            {/* Footer */}
            <footer className="app-footer">
                <div className="footer-container">
                    <div className="footer-logo">
                        <img src="/logo.png" alt="UFarm" className="footer-logo-icon" />
                        <span className="footer-logo-text">UFarm</span>
                    </div>
                    <p className="footer-text">
                        &copy; {new Date().getFullYear()} UFarm. All rights reserved.
                    </p>
                </div>
            </footer>
        </div>
    );
}

export default App;
