import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { DefaultTheme, NavigationContainer } from '@react-navigation/native';
import type { InitialState } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useEffect, useState } from 'react';
import { Text } from 'react-native';

import { ActivityListScreen } from '@/screens/ActivityListScreen';
import {
    ForgotPasswordScreen,
    LoginScreen,
    RegisterScreen,
    ResetPasswordScreen,
} from '@/screens/AuthScreens';
import { CalendarDetailScreen } from '@/screens/CalendarDetailScreen';
import { CalendarScreen } from '@/screens/CalendarScreen';
import { ContributionScreen } from '@/screens/ContributionScreen';
import { DateRangeScreen } from '@/screens/DateRangeScreen';
import { DewasaDetailScreen } from '@/screens/DewasaDetailScreen';
import { HomeScreen } from '@/screens/HomeScreen';
import { PlaceholderScreen } from '@/screens/PlaceholderScreen';
import { ProfileScreen } from '@/screens/ProfileScreen';
import { ReminderSettingsScreen } from '@/screens/ReminderSettingsScreen';
import { SavedDetailScreen } from '@/screens/SavedDetailScreen';
import { SearchResultScreen } from '@/screens/SearchResultScreen';
import { AboutScreen, SavedScreen } from '@/screens/TabScreens';
import {
    loadNavigationState,
    saveNavigationState,
} from '@/services/navigationCache';
import { useAuthStore } from '@/store/authStore';
import { colors, iconSize, typography } from '@/theme';
import type { MainTabParamList, RootStackParamList } from '@/types/navigation';

const Stack = createNativeStackNavigator<RootStackParamList>();
const Tab = createBottomTabNavigator<MainTabParamList>();

const navigationTheme = {
    ...DefaultTheme,
    colors: {
        ...DefaultTheme.colors,
        background: colors.background,
        border: colors.border,
        card: colors.surface,
        notification: colors.danger,
        primary: colors.primary,
        text: colors.text,
    },
};

const tabIcons: Record<keyof MainTabParamList, string> = {
    Home: '⌂',
    Calendar: '▦',
    Saved: '♡',
    About: 'ⓘ',
};

function MainTabs() {
    return (
        <Tab.Navigator
            screenOptions={({ route }) => ({
                headerShown: false,
                tabBarActiveTintColor: colors.primary,
                tabBarInactiveTintColor: colors.textMuted,
                tabBarLabelStyle: {
                    fontSize: typography.fontSize.caption,
                    fontWeight: typography.fontWeight.semibold,
                },
                tabBarStyle: {
                    backgroundColor: colors.surface,
                    borderTopColor: colors.border,
                    height: 70,
                    paddingBottom: 8,
                    paddingTop: 8,
                },
                // The icon depends on the current route supplied by React Navigation.
                // eslint-disable-next-line react/no-unstable-nested-components
                tabBarIcon: ({ color }) => (
                    <PlaceholderTabIcon
                        color={color}
                        symbol={tabIcons[route.name]}
                    />
                ),
            })}
        >
            <Tab.Screen
                component={HomeScreen}
                name="Home"
                options={{ tabBarLabel: 'Beranda' }}
            />
            <Tab.Screen
                component={CalendarScreen}
                name="Calendar"
                options={{ tabBarLabel: 'Kalender' }}
            />
            <Tab.Screen
                component={SavedScreen}
                name="Saved"
                options={{ tabBarLabel: 'Tersimpan' }}
            />
            <Tab.Screen
                component={AboutScreen}
                name="About"
                options={{ tabBarLabel: 'Tentang' }}
            />
        </Tab.Navigator>
    );
}

function PlaceholderTabIcon({
    color,
    symbol,
}: {
    color: string;
    symbol: string;
}) {
    return <Text style={{ color, fontSize: iconSize.lg }}>{symbol}</Text>;
}

const screens: Array<{
    name: Exclude<
        keyof RootStackParamList,
        | 'MainTabs'
        | 'ActivityList'
        | 'DateRange'
        | 'SearchResult'
        | 'DewasaDetail'
        | 'CalendarDetail'
        | 'Login'
        | 'Register'
        | 'ForgotPassword'
        | 'ResetPassword'
        | 'Profile'
        | 'SavedDetail'
        | 'ReminderSettings'
    >;
    title: string;
    description: string;
}> = [
    {
        name: 'PrivacyPolicy',
        title: 'Kebijakan Privasi',
        description:
            'Cara Wariga Bali Santih memproses, melindungi, dan memberi Anda kendali atas data.',
    },
    {
        name: 'Disclaimer',
        title: 'Disclaimer',
        description:
            'Batas penggunaan hasil, status referensi, dan pentingnya konsultasi ahli.',
    },
];

export function RootNavigator() {
    const userId = useAuthStore(state => state.user?.id ?? null);
    const [initialState, setInitialState] = useState<InitialState>();
    const [loadedScope, setLoadedScope] = useState<string | null>(null);
    const scope = `user-${userId ?? 'public'}`;

    useEffect(() => {
        let active = true;
        loadNavigationState(userId).then(state => {
            if (active) {
                setInitialState(state);
                setLoadedScope(scope);
            }
        });
        return () => {
            active = false;
        };
    }, [scope, userId]);

    if (loadedScope !== scope) return null;

    return (
        <NavigationContainer
            initialState={initialState}
            onStateChange={state =>
                saveNavigationState(userId, state).catch(() => undefined)
            }
            theme={navigationTheme}
        >
            <Stack.Navigator
                screenOptions={{
                    contentStyle: { backgroundColor: colors.background },
                    headerShadowVisible: false,
                    headerBackTitle: 'Kembali',
                    headerStyle: { backgroundColor: colors.background },
                    headerTintColor: colors.primary,
                    headerTitleStyle: {
                        color: colors.text,
                        fontWeight: typography.fontWeight.semibold,
                    },
                }}
            >
                <Stack.Screen
                    component={MainTabs}
                    name="MainTabs"
                    options={{ headerShown: false }}
                />
                <Stack.Screen
                    component={ActivityListScreen}
                    name="ActivityList"
                    options={{ title: 'Pilih Kegiatan' }}
                />
                <Stack.Screen
                    component={ContributionScreen}
                    name="Contribution"
                    options={{ title: 'Kontribusi & Saran' }}
                />
                <Stack.Screen
                    component={DateRangeScreen}
                    name="DateRange"
                    options={{ title: 'Rentang Waktu' }}
                />
                <Stack.Screen
                    component={SearchResultScreen}
                    name="SearchResult"
                    options={{ title: 'Hasil Pencarian' }}
                />
                <Stack.Screen
                    component={DewasaDetailScreen}
                    name="DewasaDetail"
                    options={{ title: 'Detail Dewasa Ayu' }}
                />
                <Stack.Screen
                    component={CalendarDetailScreen}
                    name="CalendarDetail"
                    options={{ title: 'Detail Kalender Bali' }}
                />
                <Stack.Screen
                    component={LoginScreen}
                    name="Login"
                    options={{ title: 'Masuk' }}
                />
                <Stack.Screen
                    component={RegisterScreen}
                    name="Register"
                    options={{ title: 'Daftar' }}
                />
                <Stack.Screen
                    component={ForgotPasswordScreen}
                    name="ForgotPassword"
                    options={{ title: 'Lupa Kata Sandi' }}
                />
                <Stack.Screen
                    component={ResetPasswordScreen}
                    name="ResetPassword"
                    options={{ title: 'Reset Kata Sandi' }}
                />
                <Stack.Screen
                    component={ProfileScreen}
                    name="Profile"
                    options={{ title: 'Akun' }}
                />
                <Stack.Screen
                    component={SavedDetailScreen}
                    name="SavedDetail"
                    options={{ title: 'Detail Tersimpan' }}
                />
                <Stack.Screen
                    component={ReminderSettingsScreen}
                    name="ReminderSettings"
                    options={{ title: 'Pengingat' }}
                />
                {screens.map(screen => (
                    <Stack.Screen
                        key={screen.name}
                        name={screen.name}
                        options={{ title: screen.title }}
                    >
                        {props => (
                            <PlaceholderScreen
                                {...props}
                                description={screen.description}
                                title={screen.title}
                            />
                        )}
                    </Stack.Screen>
                ))}
            </Stack.Navigator>
        </NavigationContainer>
    );
}
