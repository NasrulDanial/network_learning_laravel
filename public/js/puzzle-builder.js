/**
 * NetLearn Puzzle Authoring Studio - Frontend State & Data Engine
 * Handles:
 * 1. Dynamic 4-category state switching with spring transitions
 * 2. Real-time bitwise Subnet Calculator engine & terminal emulator update
 * 3. Dynamic Protocol-to-Layer mapping repeater (Add/Remove rows)
 * 4. Interactive XP Award slider live badge update
 * 5. Comprehensive JSON serialization for Laravel Controller consumption
 */

(function () {
    'use strict';

    // ── Application State ──
    const state = {
        activeCategory: 'topology', // 'topology' | 'routing' | 'protocol' | 'subnet'
        xpAward: 150,
        clearanceLevel: 1,
    };

    // Category Metadata
    const categoryMeta = {
        topology: {
            title: 'Topology Device Mapping Mission',
            badge: 'TOPOLOGY'
        },
        routing: {
            title: 'Packet Routing & Dijkstra Pathfinding Mission',
            badge: 'ROUTING_MAZE'
        },
        protocol: {
            title: 'OSI 7-Layer Protocol Encapsulation Mission',
            badge: 'PROTOCOL_MATCHER'
        },
        subnet: {
            title: 'VLSM CIDR Bitmask Challenge Mission',
            badge: 'SUBNET_CALCULATOR'
        }
    };

    // ── 1. Category Switcher ──
    function initCategorySwitcher() {
        const buttons = document.querySelectorAll('.segmented-btn');
        const canvasPanels = document.querySelectorAll('.canvas-panel');
        const inspectorPanels = document.querySelectorAll('.inspector-form-group');
        const missionTitleDisplay = document.getElementById('currentMissionTitleDisplay');
        const categoryBadge = document.getElementById('inspectorCategoryBadge');

        buttons.forEach(btn => {
            btn.addEventListener('click', function () {
                const targetCategory = this.getAttribute('data-category');
                if (!targetCategory || targetCategory === state.activeCategory) return;

                // Update active state
                state.activeCategory = targetCategory;

                // Update segmented control buttons
                buttons.forEach(b => {
                    b.classList.remove('active');
                    b.setAttribute('aria-selected', 'false');
                });
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');

                // Switch Canvas Panes with smooth spring transitions
                canvasPanels.forEach(panel => {
                    if (panel.id === `canvas-${targetCategory}`) {
                        panel.classList.add('active');
                    } else {
                        panel.classList.remove('active');
                    }
                });

                // Switch Inspector Form Panes
                inspectorPanels.forEach(panel => {
                    if (panel.id === `inspector-${targetCategory}`) {
                        panel.classList.add('active');
                    } else {
                        panel.classList.remove('active');
                    }
                });

                // Update title & badges
                const meta = categoryMeta[targetCategory];
                if (meta) {
                    if (missionTitleDisplay) missionTitleDisplay.textContent = meta.title;
                    if (categoryBadge) categoryBadge.textContent = meta.badge;
                }

                // If switching to subnet, recalculate immediately to sync terminal
                if (targetCategory === 'subnet') {
                    calculateSubnet();
                }
            });
        });
    }

    // ── 2. Real-Time Bitwise Subnet Calculator Engine ──
    function calculateSubnet() {
        const baseIpInput = document.getElementById('subnet_base_ip');
        const hostsInput = document.getElementById('subnet_required_hosts');

        if (!baseIpInput || !hostsInput) return;

        const baseIp = baseIpInput.value.trim() || '192.168.10.0';
        const requiredHosts = parseInt(hostsInput.value, 10) || 30;

        // Bitwise calculation: needed bits = ceil(log2(hosts + 2))
        const hostBits = Math.max(2, Math.ceil(Math.log2(requiredHosts + 2)));
        const prefix = 32 - hostBits;
        const totalAddresses = Math.pow(2, hostBits);
        const usableHosts = totalAddresses - 2;

        // Calculate Subnet Mask
        const maskInt = (0xFFFFFFFF << hostBits) >>> 0;
        const maskOctets = [
            (maskInt >>> 24) & 255,
            (maskInt >>> 16) & 255,
            (maskInt >>> 8) & 255,
            maskInt & 255
        ];
        const maskString = maskOctets.join('.');

        // Parse base IP octets
        const ipOctets = baseIp.split('.').map(o => parseInt(o, 10) || 0);
        while (ipOctets.length < 4) ipOctets.push(0);

        // Network ID
        const netOctets = [
            ipOctets[0] & maskOctets[0],
            ipOctets[1] & maskOctets[1],
            ipOctets[2] & maskOctets[2],
            ipOctets[3] & maskOctets[3]
        ];
        const netIdString = netOctets.join('.');

        // First usable IP
        const firstUsable = `${netOctets[0]}.${netOctets[1]}.${netOctets[2]}.${netOctets[3] + 1}`;

        // Broadcast IP
        const bcastOctets = [
            netOctets[0] | (~maskOctets[0] & 255),
            netOctets[1] | (~maskOctets[1] & 255),
            netOctets[2] | (~maskOctets[2] & 255),
            netOctets[3] | (~maskOctets[3] & 255)
        ];
        const bcastString = bcastOctets.join('.');

        // Last usable IP
        const lastUsable = `${bcastOctets[0]}.${bcastOctets[1]}.${bcastOctets[2]}.${bcastOctets[3] - 1}`;
        const rangeString = `${firstUsable} - ${lastUsable}`;

        // Update Context Inspector Blueprint Card
        const elMask = document.getElementById('calcMask');
        const elCidr = document.getElementById('calcCidr');
        const elNetId = document.getElementById('calcNetId');
        const elRange = document.getElementById('calcRange');
        const elBroadcast = document.getElementById('calcBroadcast');

        if (elMask) elMask.textContent = maskString;
        if (elCidr) elCidr.textContent = `/${prefix}`;
        if (elNetId) elNetId.textContent = netIdString;
        if (elRange) elRange.textContent = rangeString;
        if (elBroadcast) elBroadcast.textContent = bcastString;

        // Sync Terminal Emulator Display
        const termBaseIp = document.getElementById('termBaseIpDisplay');
        const termHosts = document.getElementById('termHostsDisplay');
        const termBits = document.getElementById('termBitsNeeded');
        const termMask = document.getElementById('termMaskDisplay');
        const termNet = document.getElementById('termNetId');
        const termRange = document.getElementById('termHostRange');
        const termBcast = document.getElementById('termBroadcast');

        if (termBaseIp) termBaseIp.textContent = baseIp;
        if (termHosts) termHosts.textContent = requiredHosts;
        if (termBits) termBits.textContent = `${hostBits} bits (2^${hostBits} = ${totalAddresses} addresses)`;
        if (termMask) termMask.textContent = `${maskString} (/${prefix})`;
        if (termNet) termNet.textContent = netIdString;
        if (termRange) termRange.textContent = rangeString;
        if (termBcast) termBcast.textContent = bcastString;
    }

    function initSubnetListeners() {
        const baseIpInput = document.getElementById('subnet_base_ip');
        const hostsInput = document.getElementById('subnet_required_hosts');

        if (baseIpInput) baseIpInput.addEventListener('input', calculateSubnet);
        if (hostsInput) hostsInput.addEventListener('input', calculateSubnet);
    }

    // ── 3. Dynamic Protocol Mapping Repeater ──
    function initProtocolRepeater() {
        const container = document.getElementById('protocolMappingsContainer');
        const btnAdd = document.getElementById('btnAddProtocol');

        if (!container || !btnAdd) return;

        btnAdd.addEventListener('click', () => {
            const rowCount = container.querySelectorAll('.protocol-row').length;
            const newRow = document.createElement('div');
            newRow.className = 'protocol-row';
            newRow.setAttribute('data-index', rowCount);
            newRow.innerHTML = `
                <input type="text" class="macos-input proto-name-input" placeholder="e.g. BGP, DNS" value="">
                <select class="macos-select proto-layer-select">
                    <option value="7">L7 - Application</option>
                    <option value="6">L6 - Presentation</option>
                    <option value="5">L5 - Session</option>
                    <option value="4">L4 - Transport</option>
                    <option value="3">L3 - Network</option>
                    <option value="2">L2 - Data Link</option>
                    <option value="1">L1 - Physical</option>
                </select>
                <button type="button" class="btn-remove-row" title="Remove Mapping"><i class="fa-solid fa-xmark"></i></button>
            `;
            container.appendChild(newRow);

            const input = newRow.querySelector('.proto-name-input');
            if (input) input.focus();
        });

        container.addEventListener('click', (e) => {
            const btnRemove = e.target.closest('.btn-remove-row');
            if (!btnRemove) return;

            const row = btnRemove.closest('.protocol-row');
            if (!row) return;

            // Only allow removing if more than 1 row exists
            if (container.querySelectorAll('.protocol-row').length > 1) {
                row.style.transform = 'translateX(10px)';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 200);
            } else {
                alert('At least one protocol mapping rule is required.');
            }
        });
    }

    // ── 4. Global Gamification Listeners ──
    function initGamificationListeners() {
        const xpSlider = document.getElementById('gamify_xp_slider');
        const xpBadge = document.getElementById('xpDisplayBadge');

        if (xpSlider && xpBadge) {
            xpSlider.addEventListener('input', function () {
                const val = parseInt(this.value, 10);
                state.xpAward = val;
                xpBadge.textContent = `+${val} XP`;
            });
        }
    }

    // ── 5. Data Serialization: Packages Active Config into JSON Payload ──
    window.serializePuzzle = function () {
        const xpVal = parseInt(document.getElementById('gamify_xp_slider')?.value || 150, 10);
        const clearanceLevel = parseInt(document.getElementById('gamify_clearance_level')?.value || 1, 10);

        const payload = {
            category: state.activeCategory,
            mission_title: categoryMeta[state.activeCategory]?.title || 'Interactive Networking Puzzle',
            global_gamification: {
                xp_award: xpVal,
                required_clearance_level: clearanceLevel,
                security_tier_label: document.getElementById('gamify_clearance_level')?.selectedOptions[0]?.text || ''
            },
            created_at: new Date().toISOString(),
            parameters: {}
        };

        // Specific Category Serializations
        if (state.activeCategory === 'topology') {
            payload.parameters = {
                gateway_device_ip: document.getElementById('topo_device_ip')?.value || '192.168.1.1',
                target_subnet_mask: document.getElementById('topo_subnet_mask')?.value || '255.255.255.0',
                default_gateway: document.getElementById('topo_gateway')?.value || '192.168.1.254',
                win_conditions: {
                    require_test_ping_echo: document.getElementById('topo_test_ping')?.checked ?? true,
                    enforce_collision_isolation: document.getElementById('topo_broadcast_isolation')?.checked ?? true
                },
                initial_topology_nodes: [
                    { id: 'node_1', type: 'router', name: 'Core-R1', ip: '192.168.1.1', x: 110, y: 90 },
                    { id: 'node_2', type: 'switch', name: 'Distribution-SW01', vlan: 10, x: 310, y: 220 },
                    { id: 'node_3', type: 'host', name: 'Client-Host-A', ip_mode: 'dhcp', x: 520, y: 90 }
                ]
            };
        } else if (state.activeCategory === 'routing') {
            const rawPorts = document.getElementById('routing_blocked_ports')?.value || '80, 23';
            const blockedPorts = rawPorts.split(',').map(p => p.trim()).filter(Boolean);

            payload.parameters = {
                blocked_ports: blockedPorts,
                max_optimal_metric: parseInt(document.getElementById('routing_path_metric')?.value || 25, 10),
                packet_ttl_limit_seconds: parseInt(document.getElementById('routing_ttl_limit')?.value || 45, 10),
                ingress_node: { name: 'INGRESS (SRC)', ip: '10.0.0.1' },
                egress_node: { name: 'EGRESS (DEST)', ip: '10.0.100.254' },
                algorithm: 'Dijkstra Shortest Path First (OSPF)'
            };
        } else if (state.activeCategory === 'protocol') {
            const mappings = [];
            document.querySelectorAll('#protocolMappingsContainer .protocol-row').forEach(row => {
                const name = row.querySelector('.proto-name-input')?.value.trim();
                const layer = parseInt(row.querySelector('.proto-layer-select')?.value, 10);
                if (name) {
                    mappings.push({
                        protocol: name,
                        correct_osi_layer: layer,
                        layer_label: row.querySelector('.proto-layer-select')?.selectedOptions[0]?.text || ''
                    });
                }
            });

            payload.parameters = {
                target_pdu_stack: 'OSI 7-Layer Hierarchy',
                protocol_answer_keys: mappings
            };
        } else if (state.activeCategory === 'subnet') {
            const baseIp = document.getElementById('subnet_base_ip')?.value.trim() || '192.168.10.0';
            const reqHosts = parseInt(document.getElementById('subnet_required_hosts')?.value || 30, 10);

            payload.parameters = {
                base_network_address: baseIp,
                required_hosts_per_subnet: reqHosts,
                calculated_win_state: {
                    subnet_mask: document.getElementById('calcMask')?.textContent || '255.255.255.224',
                    cidr_prefix: document.getElementById('calcCidr')?.textContent || '/27',
                    network_id: document.getElementById('calcNetId')?.textContent || baseIp,
                    usable_host_range: document.getElementById('calcRange')?.textContent || '',
                    broadcast_address: document.getElementById('calcBroadcast')?.textContent || ''
                }
            };
        }

        return payload;
    };

    // ── 6. JSON Modal Preview & Deploy Handlers ──
    function initModalHandlers() {
        const btnPreview = document.getElementById('btnPreviewJson');
        const btnDeploy = document.getElementById('btnDeployMission');
        const modal = document.getElementById('jsonModal');
        const btnClose = document.getElementById('btnCloseJsonModal');
        const codeOutput = document.getElementById('jsonCodeOutput');
        const btnCopy = document.getElementById('btnCopyJson');
        const btnConfirm = document.getElementById('btnConfirmDeploy');

        if (!modal) return;

        function showJsonModal() {
            const payload = window.serializePuzzle();
            if (codeOutput) {
                codeOutput.textContent = JSON.stringify(payload, null, 2);
            }
            modal.classList.add('show');
            console.log('[NetLearn Studio] Serialized Mission Configuration:', payload);
        }

        function hideJsonModal() {
            modal.classList.remove('show');
        }

        if (btnPreview) btnPreview.addEventListener('click', showJsonModal);
        if (btnDeploy) btnDeploy.addEventListener('click', showJsonModal);
        if (btnClose) btnClose.addEventListener('click', hideJsonModal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) hideJsonModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                hideJsonModal();
            }
        });

        if (btnCopy) {
            btnCopy.addEventListener('click', () => {
                const text = codeOutput?.textContent || '';
                navigator.clipboard.writeText(text).then(() => {
                    const originalHtml = btnCopy.innerHTML;
                    btnCopy.innerHTML = `<i class="fa-solid fa-check text-cyan"></i> <span>Copied!</span>`;
                    setTimeout(() => { btnCopy.innerHTML = originalHtml; }, 1800);
                });
            });
        }

        if (btnConfirm) {
            btnConfirm.addEventListener('click', () => {
                const payload = window.serializePuzzle();
                btnConfirm.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> <span>Transmitting...</span>`;
                btnConfirm.disabled = true;

                // Simulated POST deployment to Laravel endpoint
                setTimeout(() => {
                    btnConfirm.innerHTML = `<i class="fa-solid fa-check"></i> <span>Mission Deployed!</span>`;
                    setTimeout(() => {
                        hideJsonModal();
                        btnConfirm.innerHTML = `<i class="fa-solid fa-check"></i> <span>Confirm & Store</span>`;
                        btnConfirm.disabled = false;
                        alert(`Success! "${payload.mission_title}" has been packaged and registered.`);
                    }, 800);
                }, 700);
            });
        }
    }

    // ── Master Initializer ──
    document.addEventListener('DOMContentLoaded', () => {
        initCategorySwitcher();
        initSubnetListeners();
        initProtocolRepeater();
        initGamificationListeners();
        initModalHandlers();
        calculateSubnet();
    });

})();
