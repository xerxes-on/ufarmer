<div class="prose dark:prose-invert max-w-none">
    <h3>Available Functions</h3>
    
    <h4>Basic Functions</h4>
    <ul>
        <li><code>min(a, b, ...)</code> - Returns minimum value</li>
        <li><code>max(a, b, ...)</code> - Returns maximum value</li>
        <li><code>abs(x)</code> - Returns absolute value</li>
        <li><code>get("path", default)</code> - Gets value from context (e.g., get("soil.ph", 7))</li>
    </ul>

    <h4>Custom Functions</h4>
    <ul>
        <li><code>in_range(value, min, max)</code> - Returns 1.0 if value is within range, proportional factor otherwise</li>
        <li><code>piecewise_ratio(actual, optimal)</code> - Returns factor based on actual/optimal ratio using NPK bands</li>
        <li><code>ec_decline(ec, threshold, slope)</code> - Returns salinity stress factor (1.0 below threshold, declining above)</li>
        <li><code>nematodes_piecewise(count)</code> - Returns factor based on nematode count breakpoints</li>
        <li><code>risk_from(f_ec, f_water, f_temp)</code> - Returns risk level: "low", "medium", or "high"</li>
    </ul>

    <h4>Available Variables</h4>
    <table class="min-w-full">
        <thead>
            <tr>
                <th>Variable Path</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>params.baseline_yield</code></td>
                <td>Baseline yield for the crop (t/ha)</td>
            </tr>
            <tr>
                <td><code>params.humus_min, params.humus_max</code></td>
                <td>Optimal humus range</td>
            </tr>
            <tr>
                <td><code>soil.humus</code></td>
                <td>Measured soil humus %</td>
            </tr>
            <tr>
                <td><code>soil.ph</code></td>
                <td>Measured soil pH</td>
            </tr>
            <tr>
                <td><code>soil.n, soil.p, soil.k</code></td>
                <td>Available N, P, K in kg/ha</td>
            </tr>
            <tr>
                <td><code>soil.ec_soil</code></td>
                <td>Soil electrical conductivity (dS/m)</td>
            </tr>
            <tr>
                <td><code>soil.nematodes</code></td>
                <td>Nematode count per kg</td>
            </tr>
            <tr>
                <td><code>water.available</code></td>
                <td>Available water in mm</td>
            </tr>
            <tr>
                <td><code>water.ph</code></td>
                <td>Irrigation water pH</td>
            </tr>
            <tr>
                <td><code>climate.avg_temp</code></td>
                <td>Average temperature (°C)</td>
            </tr>
            <tr>
                <td><code>biotic.diseases, biotic.pests, biotic.weeds</code></td>
                <td>Biotic stress factors (0-1)</td>
            </tr>
            <tr>
                <td><code>f_*</code></td>
                <td>Any previously calculated factor</td>
            </tr>
        </tbody>
    </table>

    <h4>NPK Piecewise Bands</h4>
    <p>Default bands for NPK ratio calculations:</p>
    <ul>
        <li>0.8 - 1.2 → 1.0 (optimal)</li>
        <li>0.5 - 0.8 or 1.2 - 1.5 → 0.8</li>
        <li>0.3 - 0.5 or 1.5 - 2.0 → 0.6</li>
        <li>Otherwise → 0.3</li>
    </ul>

    <h4>Nematode Breakpoints</h4>
    <p>Default breakpoints for nematode stress:</p>
    <ul>
        <li>≤ 200 J2/kg → 0.88</li>
        <li>≤ 400 J2/kg → 0.77</li>
        <li>≤ 800 J2/kg → 0.60</li>
        <li>> 800 J2/kg → 0.40</li>
    </ul>
</div>