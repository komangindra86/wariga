import type { NavigatorScreenParams } from '@react-navigation/native';

export type MainTabParamList = {
    Home: undefined;
    Search: undefined;
    Calendar: undefined;
    Saved: undefined;
    About: undefined;
};

export type RootStackParamList = {
    MainTabs: NavigatorScreenParams<MainTabParamList> | undefined;
    // date: cek satu tanggal tertentu (dari Detail Kalender) untuk kegiatan yang dipilih.
    ActivityList: { date?: string } | undefined;
    DateRange: { activityId: number };
    SearchResult:
        | { activityId?: number; startDate?: string; endDate?: string }
        | undefined;
    DewasaDetail: { activity: string; date: string };
    CalendarDetail: { date: string };
    Login: undefined;
    Register: undefined;
    ForgotPassword: undefined;
    ResetPassword: { email?: string; token?: string } | undefined;
    SavedDetail: { savedId: number };
    ReminderSettings: undefined;
    Profile: undefined;
    PrivacyPolicy: undefined;
    Disclaimer: undefined;
    Contribution: undefined;
};
