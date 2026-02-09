import { useEffect, useState } from 'react';
import { api } from '../../services/api';
import { Shield, TerminalSquare, Filter } from 'lucide-react';

export default function OpsCenter() {
    const [logMeta, setLogMeta] = useState(null);
    const [logType, setLogType] = useState('php_fpm');
    const [domainId, setDomainId] = useState('');
    const [logLines, setLogLines] = useState('');
    const [logCount, setLogCount] = useState(200);

    const [scans, setScans] = useState([]);
    const [scanPath, setScanPath] = useState('');
    const [auditPath, setAuditPath] = useState('');
    const [baselines, setBaselines] = useState([]);
    const [baselineName, setBaselineName] = useState('Default Baseline');
    const [baselinePaths, setBaselinePaths] = useState('');
    const [selectedBaselineId, setSelectedBaselineId] = useState('');
    const [integrityChecks, setIntegrityChecks] = useState([]);

    const [rules, setRules] = useState([]);
    const [ruleForm, setRuleForm] = useState({ action: 'allow', protocol: 'tcp', port: '80', source: '', description: '' });
    const [firewallStatus, setFirewallStatus] = useState('');

    useEffect(() => {
        loadMeta();
        loadScans();
        loadBaselines();
        loadRules();
    }, []);

    const loadMeta = async () => {
        const res = await api.admin.getLogMeta();
        setLogMeta(res);
        if (res?.domains?.length) {
            setDomainId(String(res.domains[0].id));
        }
    };

    const loadLogs = async () => {
        const params = { type: logType, lines: logCount };
        if (logType.startsWith('domain')) {
            params.domain_id = domainId;
        }
        const res = await api.admin.tailLogs(params);
        setLogLines(res?.lines || '');
    };

    const loadScans = async () => {
        const res = await api.admin.getSecurityScans();
        setScans(res?.data || []);
    };

    const runScan = async (type = 'malware') => {
        const path = type === 'audit' ? auditPath : scanPath;
        await api.admin.runSecurityScan(path || undefined, type);
        await loadScans();
    };

    const loadBaselines = async () => {
        const res = await api.admin.getSecurityBaselines();
        const list = res?.data || [];
        setBaselines(list);
        if (!selectedBaselineId && list.length) {
            setSelectedBaselineId(String(list[0].id));
            await loadIntegrityChecks(String(list[0].id));
        }
    };

    const createBaseline = async () => {
        const paths = baselinePaths
            ? baselinePaths.split(',').map((p) => p.trim()).filter(Boolean)
            : undefined;
        await api.admin.createSecurityBaseline({ name: baselineName, paths });
        setBaselineName('Default Baseline');
        setBaselinePaths('');
        await loadBaselines();
    };

    const loadIntegrityChecks = async (baselineId) => {
        const res = await api.admin.getIntegrityChecks(baselineId, 1);
        setIntegrityChecks(res?.data || []);
    };

    const runIntegrityCheck = async () => {
        if (!selectedBaselineId) return;
        await api.admin.runIntegrityCheck(selectedBaselineId);
        await loadIntegrityChecks(selectedBaselineId);
    };

    const loadRules = async () => {
        const res = await api.admin.getFirewallRules();
        setRules(res?.data || []);
    };

    const createRule = async () => {
        await api.admin.createFirewallRule(ruleForm);
        setRuleForm({ action: 'allow', protocol: 'tcp', port: '80', source: '', description: '' });
        await loadRules();
    };

    const deleteRule = async (id) => {
        await api.admin.deleteFirewallRule(id);
        await loadRules();
    };

    const applyRules = async () => {
        await api.admin.applyFirewallRules();
        await loadRules();
    };

    const refreshStatus = async () => {
        const res = await api.admin.getFirewallStatus();
        setFirewallStatus(res?.data || '');
    };

    return (
        <div className="p-6 space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-gray-900">Ops Center</h1>
                <p className="text-sm text-gray-500">Logs, security scans, and firewall rules.</p>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                    <div className="flex items-center gap-2 text-gray-700 mb-4">
                        <TerminalSquare className="w-4 h-4" />
                        <h2 className="text-sm font-semibold">Log Viewer</h2>
                    </div>
                    <div className="flex flex-wrap gap-2 text-xs">
                        <select value={logType} onChange={(e) => setLogType(e.target.value)} className="rounded-lg border border-gray-200 px-3 py-2">
                            <option value="php_fpm">PHP-FPM</option>
                            <option value="domain_access">Domain Access</option>
                            <option value="domain_error">Domain Error</option>
                        </select>
                        {(logType === 'domain_access' || logType === 'domain_error') && (
                            <select value={domainId} onChange={(e) => setDomainId(e.target.value)} className="rounded-lg border border-gray-200 px-3 py-2">
                                {logMeta?.domains?.map((domain) => (
                                    <option key={domain.id} value={domain.id}>{domain.hostname}</option>
                                ))}
                            </select>
                        )}
                        <input
                            type="number"
                            value={logCount}
                            onChange={(e) => setLogCount(Number(e.target.value))}
                            className="w-24 rounded-lg border border-gray-200 px-3 py-2"
                        />
                        <button onClick={loadLogs} className="px-3 py-2 rounded-lg border border-gray-200">Load</button>
                    </div>
                    <pre className="mt-4 max-h-72 overflow-auto rounded-lg bg-gray-900 text-green-200 text-[11px] p-3 whitespace-pre-wrap">{logLines || 'No logs loaded.'}</pre>
                </div>

                <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                    <div className="flex items-center gap-2 text-gray-700 mb-4">
                        <Shield className="w-4 h-4" />
                        <h2 className="text-sm font-semibold">Security Scan & Audit</h2>
                    </div>
                    <div className="space-y-3 text-xs">
                        <div className="flex gap-2">
                            <input
                                value={scanPath}
                                onChange={(e) => setScanPath(e.target.value)}
                                className="flex-1 rounded-lg border border-gray-200 px-3 py-2"
                                placeholder="Malware scan path (optional)"
                            />
                            <button onClick={() => runScan('malware')} className="px-3 py-2 rounded-lg bg-gray-900 text-white">Run Scan</button>
                        </div>
                        <div className="flex gap-2">
                            <input
                                value={auditPath}
                                onChange={(e) => setAuditPath(e.target.value)}
                                className="flex-1 rounded-lg border border-gray-200 px-3 py-2"
                                placeholder="Audit path (optional)"
                            />
                            <button onClick={() => runScan('audit')} className="px-3 py-2 rounded-lg border border-gray-200">Run Audit</button>
                        </div>
                    </div>
                    <div className="mt-4 space-y-2 text-xs">
                        {scans.map((scan) => (
                            <div key={scan.id} className="border border-gray-100 rounded-lg px-3 py-2">
                                <div className="flex items-center justify-between">
                                    <span className="font-semibold">{scan.status} • {scan.type}</span>
                                    <span className="text-gray-400">{scan.started_at}</span>
                                </div>
                                <p className="text-[11px] text-gray-500">{scan.target_path}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <div className="flex items-center gap-2 text-gray-700 mb-4">
                    <Shield className="w-4 h-4" />
                    <h2 className="text-sm font-semibold">File Integrity</h2>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-2 text-xs">
                    <input
                        value={baselineName}
                        onChange={(e) => setBaselineName(e.target.value)}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Baseline name"
                    />
                    <input
                        value={baselinePaths}
                        onChange={(e) => setBaselinePaths(e.target.value)}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Paths (comma separated, optional)"
                    />
                    <button onClick={createBaseline} className="px-3 py-2 rounded-lg bg-gray-900 text-white">Create Baseline</button>
                </div>

                <div className="mt-3 flex flex-wrap gap-2 text-xs">
                    <select
                        value={selectedBaselineId}
                        onChange={(e) => {
                            setSelectedBaselineId(e.target.value);
                            loadIntegrityChecks(e.target.value);
                        }}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                    >
                        {baselines.map((baseline) => (
                            <option key={baseline.id} value={baseline.id}>{baseline.name}</option>
                        ))}
                    </select>
                    <button onClick={runIntegrityCheck} className="px-3 py-2 rounded-lg border border-gray-200">Run Integrity Check</button>
                </div>

                <div className="mt-4 space-y-2 text-xs">
                    {integrityChecks.map((check) => (
                        <div key={check.id} className="border border-gray-100 rounded-lg px-3 py-2">
                            <div className="flex items-center justify-between">
                                <span className="font-semibold">{check.status}</span>
                                <span className="text-gray-400">{check.started_at}</span>
                            </div>
                            <pre className="mt-2 bg-gray-900 text-green-200 text-[11px] p-2 rounded-lg whitespace-pre-wrap">
                                {check.output || 'No output'}
                            </pre>
                        </div>
                    ))}
                </div>
            </div>

            <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                <div className="flex items-center gap-2 text-gray-700 mb-4">
                    <Filter className="w-4 h-4" />
                    <h2 className="text-sm font-semibold">Firewall Rules (UFW)</h2>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-5 gap-2 text-xs">
                    <select value={ruleForm.action} onChange={(e) => setRuleForm({ ...ruleForm, action: e.target.value })} className="rounded-lg border border-gray-200 px-3 py-2">
                        <option value="allow">Allow</option>
                        <option value="deny">Deny</option>
                    </select>
                    <select value={ruleForm.protocol} onChange={(e) => setRuleForm({ ...ruleForm, protocol: e.target.value })} className="rounded-lg border border-gray-200 px-3 py-2">
                        <option value="tcp">TCP</option>
                        <option value="udp">UDP</option>
                    </select>
                    <input
                        value={ruleForm.port}
                        onChange={(e) => setRuleForm({ ...ruleForm, port: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Port"
                    />
                    <input
                        value={ruleForm.source}
                        onChange={(e) => setRuleForm({ ...ruleForm, source: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Source (optional)"
                    />
                    <input
                        value={ruleForm.description}
                        onChange={(e) => setRuleForm({ ...ruleForm, description: e.target.value })}
                        className="rounded-lg border border-gray-200 px-3 py-2"
                        placeholder="Description"
                    />
                </div>
                <div className="mt-3 flex gap-2 text-xs">
                    <button onClick={createRule} className="px-3 py-2 rounded-lg bg-gray-900 text-white">Add Rule</button>
                    <button onClick={applyRules} className="px-3 py-2 rounded-lg border border-gray-200">Apply Rules</button>
                    <button onClick={refreshStatus} className="px-3 py-2 rounded-lg border border-gray-200">UFW Status</button>
                </div>

                <div className="mt-4 space-y-2 text-xs">
                    {rules.map((rule) => (
                        <div key={rule.id} className="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2">
                            <div>
                                <p className="font-semibold">{rule.action} {rule.port}/{rule.protocol}</p>
                                <p className="text-[11px] text-gray-500">{rule.source || 'any'} • {rule.description || 'no description'}</p>
                            </div>
                            <button onClick={() => deleteRule(rule.id)} className="text-xs text-rose-600">Delete</button>
                        </div>
                    ))}
                </div>

                {firewallStatus ? (
                    <pre className="mt-4 rounded-lg bg-gray-900 text-green-200 text-[11px] p-3 whitespace-pre-wrap">{firewallStatus}</pre>
                ) : null}
            </div>
        </div>
    );
}
