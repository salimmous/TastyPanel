import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import Header from './components/Header';
import Footer from './components/Footer';
import Home from './pages/Home';
import Category from './pages/Category';
import RecipeDetail from './pages/RecipeDetail';
import About from './pages/About';
import Contact from './pages/Contact';
import Login from './pages/Login';
import AdminLayout from './layouts/AdminLayout';
import AdminDashboard from './pages/AdminDashboard';
import Setup from './pages/admin/Setup';
import ForcePassword from './pages/admin/ForcePassword';
import TwoFactor from './pages/admin/TwoFactor';
import Posts from './pages/admin/Posts';
import AIWriter from './pages/admin/AIWriter';
import Categories from './pages/admin/Categories';
import Archive from './pages/admin/Archive';
import Advertisement from './pages/admin/Advertisement';
import RssFeeds from './pages/admin/RssFeeds';
import Reporter from './pages/admin/Reporter';
import Opinions from './pages/admin/Opinions';
import Polls from './pages/admin/Polls';
import VideoPost from './pages/admin/VideoPost';
import Page from './pages/admin/Page';
import SEO from './pages/admin/SEO';
import AutoPostSettings from './pages/admin/AutoPostSettings';
import WebSetup from './pages/admin/WebSetup';
import Settings from './pages/admin/Settings';
import Subscribers from './pages/admin/Subscribers';
import Users from './pages/admin/Users';
import Media from './pages/admin/Media';
import Tenants from './pages/admin/Tenants';
import Themes from './pages/admin/Themes';
import Platform from './pages/admin/Platform';
import Analytics from './pages/admin/Analytics';
import Integrations from './pages/admin/Integrations';
import Plugins from './pages/admin/Plugins';
import OpsCenter from './pages/admin/OpsCenter';
import Monitoring from './pages/admin/Monitoring';
import Activity from './pages/admin/Activity';
import ThemeMarketplace from './pages/admin/ThemeMarketplace';
import Staging from './pages/admin/Staging';
import Preview from './pages/admin/Preview';
import FileManager from './pages/admin/FileManager';
import FeatureFlags from './pages/admin/FeatureFlags';

function App() {
  return (
    <Router>
      <Routes>
        {/* Admin Routes */}
        <Route path="/login" element={<Login />} />
        <Route path="/admin/setup" element={<Setup />} />
        <Route path="/admin/force-password" element={<ForcePassword />} />
        <Route path="/admin/2fa" element={<TwoFactor />} />
        <Route path="/dashboard" element={<Navigate to="/admin" replace />} />
        <Route path="/admin" element={<AdminLayout />}>
          <Route index element={<AdminDashboard />} />
          <Route path="dashboard" element={<AdminDashboard />} />
          <Route path="posts" element={<Posts />} />
          <Route path="ai-writer" element={<AIWriter />} />
          <Route path="categories" element={<Categories />} />
          <Route path="archive" element={<Archive />} />
          <Route path="advertisement" element={<Advertisement />} />
          <Route path="rss-feeds" element={<RssFeeds />} />
          <Route path="reporter" element={<Reporter />} />
          <Route path="opinions" element={<Opinions />} />
          <Route path="polls" element={<Polls />} />
          <Route path="video-post" element={<VideoPost />} />
          <Route path="page" element={<Page />} />
          <Route path="seo" element={<SEO />} />
          <Route path="auto-post-settings" element={<AutoPostSettings />} />
          <Route path="web-setup" element={<WebSetup />} />
          <Route path="settings" element={<Settings />} />
          <Route path="subscribers" element={<Subscribers />} />
          <Route path="users" element={<Users />} />
          <Route path="media" element={<Media />} />
          <Route path="platform" element={<Platform />} />
          <Route path="tenants" element={<Tenants />} />
          <Route path="themes" element={<Themes />} />
          <Route path="analytics" element={<Analytics />} />
          <Route path="feature-flags" element={<FeatureFlags />} />
          <Route path="integrations" element={<Integrations />} />
          <Route path="plugins" element={<Plugins />} />
          <Route path="marketplace" element={<ThemeMarketplace />} />
          <Route path="staging" element={<Staging />} />
          <Route path="preview" element={<Preview />} />
          <Route path="files" element={<FileManager />} />
          <Route path="ops" element={<OpsCenter />} />
          <Route path="monitoring" element={<Monitoring />} />
          <Route path="activity" element={<Activity />} />
        </Route>
        
        {/* Public Routes (with Header/Footer) */}
        <Route path="/" element={
          <div className="flex flex-col min-h-screen bg-white font-sans text-text-main">
            <Header />
            <main className="flex-grow">
              <Home />
            </main>
            <Footer />
          </div>
        } />
        <Route path="/category/:id" element={
          <div className="flex flex-col min-h-screen bg-white font-sans text-text-main">
            <Header />
            <main className="flex-grow">
              <Category />
            </main>
            <Footer />
          </div>
        } />
        <Route path="/recipe/:id" element={
          <div className="flex flex-col min-h-screen bg-white font-sans text-text-main">
            <Header />
            <main className="flex-grow">
              <RecipeDetail />
            </main>
            <Footer />
          </div>
        } />
        <Route path="/about" element={
          <div className="flex flex-col min-h-screen bg-white font-sans text-text-main">
            <Header />
            <main className="flex-grow">
              <About />
            </main>
            <Footer />
          </div>
        } />
        <Route path="/contact" element={
          <div className="flex flex-col min-h-screen bg-white font-sans text-text-main">
            <Header />
            <main className="flex-grow">
              <Contact />
            </main>
            <Footer />
          </div>
        } />
      </Routes>
    </Router>
  );
}

export default App;
