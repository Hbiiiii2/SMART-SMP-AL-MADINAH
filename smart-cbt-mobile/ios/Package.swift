// swift-tools-version: 5.9
import PackageDescription

let package = Package(
    name: "SmartCBTCore",
    platforms: [
        .iOS(.v15),
        .macOS(.v13)
    ],
    products: [
        .library(
            name: "SmartCBTCore",
            targets: ["SmartCBTCore"]
        )
    ],
    targets: [
        .target(
            name: "SmartCBTCore",
            path: "SmartCBT",
            sources: [
                "Config/SchoolConfigManager.swift",
                "Security/ExamLockController.swift"
            ]
        ),
        .testTarget(
            name: "SmartCBTTests",
            dependencies: ["SmartCBTCore"],
            path: "Tests/SmartCBTTests"
        )
    ]
)
