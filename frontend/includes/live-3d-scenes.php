<?php
if (!function_exists('renderLive3DScene')) {
    /**
     * Renders an open-space pure-code Live 3D model without a boxed container.
     */
    function renderLive3DScene(string $scene): void
    {
        if ($scene === 'hero-scanner'): ?>
            <div class="scene3d-wrapper" data-3d-wrapper>
                <div class="scene3d-stage" data-3d-stage>
                    <div class="scene3d-aura" aria-hidden="true"></div>
                    <div class="scene3d-center">
                        <div class="holo-doc-3d">
                            <span class="holo-corner tl" aria-hidden="true"></span>
                            <span class="holo-corner tr" aria-hidden="true"></span>
                            <span class="holo-corner bl" aria-hidden="true"></span>
                            <span class="holo-corner br" aria-hidden="true"></span>
                            <div class="holo-laser" aria-hidden="true"></div>

                            <div class="holo-doc-title">
                                <span>SCANNING...</span>
                                <span>300 DPI</span>
                            </div>

                            <div class="holo-lines">
                                <div class="holo-line w-90"></div>
                                <div class="holo-line w-75"></div>
                                <div class="holo-line w-90"></div>
                                <div class="holo-line w-60"></div>
                            </div>

                            <div class="holo-chart-row" aria-hidden="true">
                                <div class="holo-bar" style="height:45%;"></div>
                                <div class="holo-bar" style="height:82%;"></div>
                                <div class="holo-bar" style="height:64%;"></div>
                                <div class="holo-bar" style="height:96%;"></div>
                            </div>

                            <div class="float-badge-3d top-right">
                                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                                <span>AI OCR 99%</span>
                            </div>
                            <div class="float-badge-3d bottom-left">
                                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                                <span>AES-128 SECURED</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($scene === 'phone-scanner'): ?>
            <div class="scene3d-wrapper" data-3d-wrapper>
                <div class="scene3d-stage" data-3d-stage>
                    <div class="scene3d-aura" aria-hidden="true"></div>
                    <div class="scene3d-center">
                        <div class="phone-3d-shell">
                            <div class="phone-3d-screen">
                                <div class="phone-3d-paper">
                                    <span class="holo-corner tl" aria-hidden="true"></span>
                                    <span class="holo-corner tr" aria-hidden="true"></span>
                                    <span class="holo-corner bl" aria-hidden="true"></span>
                                    <span class="holo-corner br" aria-hidden="true"></span>
                                    <div class="holo-laser" aria-hidden="true"></div>
                                    <div class="phone-3d-paper-title">PROJECT PROPOSAL</div>
                                    <div class="phone-3d-paper-line" style="width:92%;"></div>
                                    <div class="phone-3d-paper-line" style="width:78%;"></div>
                                    <div class="phone-3d-paper-line" style="width:88%;"></div>
                                    <div class="phone-3d-paper-line" style="width:66%;"></div>
                                    <div class="phone-3d-paper-line" style="width:84%;"></div>
                                </div>
                                <div class="phone-3d-controls" aria-hidden="true">
                                    <i class="fa-solid fa-images" style="color:var(--text-secondary);font-size:0.85rem;"></i>
                                    <div class="phone-3d-shutter"></div>
                                    <i class="fa-solid fa-bolt" style="color:var(--accent-cyan);font-size:0.85rem;"></i>
                                </div>
                            </div>

                            <div class="float-badge-3d top-right">
                                <span class="scene3d-dot" aria-hidden="true"></span>
                                <span>AUTO-CROP</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($scene === 'ai-enhancement'): ?>
            <div class="scene3d-wrapper" data-3d-wrapper>
                <div class="scene3d-stage" data-3d-stage data-ai-stage>
                    <div class="scene3d-aura" aria-hidden="true"></div>
                    <div class="scene3d-center">
                        <div class="ai-enhance-stack">
                            <div class="ai-paper-3d" data-filter="magic">
                                <div class="ai-paper-head">
                                    <span>AI REPORT • DATA ANALYSIS</span>
                                    <span class="ai-badge-3d">AI PRO</span>
                                </div>
                                <div class="phone-3d-paper-line" style="width:94%;background:#334155;"></div>
                                <div class="phone-3d-paper-line" style="width:80%;background:#64748B;"></div>
                                <div class="phone-3d-paper-line" style="width:88%;background:#64748B;"></div>
                                <div class="phone-3d-paper-line" style="width:68%;background:#94A3B8;"></div>
                                <div class="holo-chart-row" style="height:36px;margin-top:10px;" aria-hidden="true">
                                    <div class="holo-bar" style="height:55%;background:#0284C7;"></div>
                                    <div class="holo-bar" style="height:90%;background:#0D9488;"></div>
                                    <div class="holo-bar" style="height:68%;background:#7C3AED;"></div>
                                    <div class="holo-bar" style="height:95%;background:#06B6D4;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="ai-filter-bar" role="group" aria-label="Preview AI Document Filters">
                            <button type="button" class="ai-filter-btn" data-filter="original">Original</button>
                            <button type="button" class="ai-filter-btn active" data-filter="magic">Magic Pro</button>
                            <button type="button" class="ai-filter-btn" data-filter="noshadow">No Shadow</button>
                            <button type="button" class="ai-filter-btn" data-filter="bw">B&amp;W Clean</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($scene === 'office-formats'): ?>
            <div class="scene3d-wrapper" data-3d-wrapper>
                <div class="scene3d-stage" data-3d-stage>
                    <div class="scene3d-aura" aria-hidden="true"></div>
                    <div class="scene3d-center">
                        <div class="vault-3d-hub">
                            <div class="vault-folder-3d">
                                <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                                <strong>DOCUMENTS VAULT</strong>
                                <span>Synced &amp; Organized</span>
                            </div>

                            <span class="orbit-chip-3d pdf">PDF</span>
                            <span class="orbit-chip-3d docx">DOCX</span>
                            <span class="orbit-chip-3d xlsx">XLSX</span>
                            <span class="orbit-chip-3d pptx">PPTX</span>
                            <span class="orbit-chip-3d zip">ZIP</span>
                            <span class="orbit-chip-3d jpg">JPG</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="scene3d-wrapper" data-3d-wrapper>
                <div class="scene3d-stage" data-3d-stage>
                    <div class="scene3d-aura" aria-hidden="true"></div>
                    <div class="scene3d-center">
                        <div class="device-3d-wrap">
                            <div class="check-orb-3d">
                                <i class="fa-solid fa-check" aria-hidden="true"></i>
                            </div>
                            <div class="scanner-body-3d">
                                <div class="scanner-screen-3d">SCANPRO AI • READY</div>
                                <div class="scanner-slot-3d"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif;
    }
}