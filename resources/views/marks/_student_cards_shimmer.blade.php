<div class="grid grid-2" style="gap: 1.5rem; width: 100%;">
    @for($i = 0; $i < 4; $i++)
        <div class="card shimmer-card" style="padding: 1.5rem; min-height: 18rem; display: flex; flex-direction: column; justify-content: space-between; border: 1px solid var(--color-scsa-line);">
            <!-- Subject Header -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem;">
                <div style="width: 65%;">
                    <div class="shimmer-placeholder" style="width: 4.5rem; height: 1.25rem; border-radius: 4px; margin-bottom: 0.5rem;"></div>
                    <div class="shimmer-placeholder" style="width: 85%; height: 1.5rem; border-radius: 4px; margin-bottom: 0.5rem;"></div>
                    <div class="shimmer-placeholder" style="width: 55%; height: 0.8125rem; border-radius: 4px;"></div>
                </div>
                <div style="width: 30%; text-align: right; display: flex; flex-direction: column; align-items: flex-end;">
                    <div class="shimmer-placeholder" style="width: 3.5rem; height: 0.725rem; border-radius: 4px; margin-bottom: 0.25rem;"></div>
                    <div class="shimmer-placeholder" style="width: 4.5rem; height: 2rem; border-radius: 4px;"></div>
                </div>
            </div>

            <!-- Custom Progress Bar -->
            <div style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                    <div class="shimmer-placeholder" style="width: 5rem; height: 0.75rem; border-radius: 4px;"></div>
                    <div class="shimmer-placeholder" style="width: 2rem; height: 0.75rem; border-radius: 4px;"></div>
                </div>
                <div class="shimmer-placeholder" style="width: 100%; height: 0.5rem; border-radius: 999px;"></div>
            </div>

            <!-- Breakdown Specifications -->
            <div style="background-color: var(--bg-primary); border-radius: var(--border-radius-lg); padding: 1rem; border: 1px solid var(--color-scsa-line); display: flex; flex-direction: column; gap: 0.75rem;">
                <div style="border-bottom: 1px dashed var(--color-scsa-line); padding-bottom: 0.5rem; display: flex; justify-content: space-between;">
                    <div class="shimmer-placeholder" style="width: 7rem; height: 0.725rem; border-radius: 4px;"></div>
                    <div class="shimmer-placeholder" style="width: 4rem; height: 0.725rem; border-radius: 4px;"></div>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="shimmer-placeholder" style="width: 10rem; height: 0.8125rem; border-radius: 4px;"></div>
                    <div class="shimmer-placeholder" style="width: 3rem; height: 0.8125rem; border-radius: 4px;"></div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="shimmer-placeholder" style="width: 8rem; height: 0.8125rem; border-radius: 4px;"></div>
                    <div class="shimmer-placeholder" style="width: 3rem; height: 0.8125rem; border-radius: 4px;"></div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--color-scsa-line); padding-top: 0.5rem;">
                    <div class="shimmer-placeholder" style="width: 11rem; height: 0.8125rem; border-radius: 4px;"></div>
                    <div class="shimmer-placeholder" style="width: 3rem; height: 0.8125rem; border-radius: 4px;"></div>
                </div>
            </div>
        </div>
    @endfor
</div>
