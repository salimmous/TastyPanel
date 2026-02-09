export const metadata = {
  title: 'TastyPanel Site',
  description: 'Tenant site powered by TastyPanel platform',
};

export default function RootLayout({ children }) {
  return (
    <html lang="en">
      <body className="antialiased bg-white text-gray-900">
        <main className="min-h-screen">{children}</main>
      </body>
    </html>
  );
}
