import { Navigate, Route, Routes } from 'react-router-dom';
import { useAuth } from './context/AuthContext';
import { CenterState, Spinner } from './ui/primitives';
import Login from './screens/Login';
import Shell from './components/Shell';
import Overview from './screens/Overview';
import Queue from './screens/Queue';
import Reports from './screens/Reports';
import Terms from './screens/Terms';
import Accounts from './screens/Accounts';

export default function App() {
  const { ready, isModerator } = useAuth();

  if (!ready) {
    return (
      <CenterState>
        <Spinner size={26} />
        <span style={{ fontSize: 13 }}>Menyiapkan konsol…</span>
      </CenterState>
    );
  }

  if (!isModerator) {
    return (
      <Routes>
        <Route path="*" element={<Login />} />
      </Routes>
    );
  }

  return (
    <Routes>
      <Route element={<Shell />}>
        <Route index element={<Navigate to="/ringkasan" replace />} />
        <Route path="/ringkasan" element={<Overview />} />
        <Route path="/antrean" element={<Queue />} />
        <Route path="/laporan" element={<Reports />} />
        <Route path="/kata-terlarang" element={<Terms />} />
        <Route path="/akun" element={<Accounts />} />
        <Route path="*" element={<Navigate to="/ringkasan" replace />} />
      </Route>
    </Routes>
  );
}
